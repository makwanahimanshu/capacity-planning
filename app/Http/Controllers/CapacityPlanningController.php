<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Leave;
use App\Models\Project;
use App\Models\Resource;
use App\Models\ResourceProjectAllocation;
use Auth, Validator, DB, Exception, Log, Str;

class CapacityPlanningController extends Controller
{
    private int $dailyWorkingHours;

    public function __construct()
    {
        $this->dailyWorkingHours = config('constants.daily_working_hours');
    }

    /**
     * Display main view for Capacity Planning module
     */
    public function index()
    {
        return view('capacity-planning.index');
    }

    /**
     * Get all active resources with their department
     */
    public function getResources(Request $request)
    {
        $projectId = $request->query('project_id');

        $query = Resource::select('id','name','dept_id','daily_capacity')
            ->with('department:id,name')
            ->whereNotIn('dept_id', [7, 8]); // Exclude departments 7 and 8

        if($projectId){
            $query->whereHas('allocations', function($q) use ($projectId){
                $q->where('project_id', $projectId);
            });
        }

        $resources = $query->get()->map(function($res){
            return [
                'id' => $res->id,
                'name' => $res->name,
                'department' => $res->department->name ?? null,
                'daily_capacity' => $res->daily_capacity,
            ];
        });

        return response()->json($resources);
    }

    /**
     * Returns the maximum available working hours for a given month.
     * 
     * This function takes into account the number of working days in the month
     * (i.e., days that are not weekends) and the number of hours per day.
     * 
     * @param string $month Month in the format YYYY-MM
     * @param int $dailyWorkingHours Number of hours per day (default: 8)
     * @return int Maximum available working hours for the given month
     */
    private function getMaxHoursMonth($month, $dailyWorkingHours)
    {

        [$year, $m] = explode('-', $month);
        $daysInMonth = Carbon::createFromDate($year, $m, 1)->daysInMonth;
        $workingDays = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($year, $m, $day);
            if (!$date->isWeekend()) {
                $workingDays++;
            }
        }

        return $workingDays * $dailyWorkingHours;
    }

    /**
     * Retrieves all leaves taken by a resource in a given month.
     * 
     * The response will contain an array of dates on which the resource took a leave,
     * as well as the total number of leave days taken in the month.
     * 
     * @param int $resourceId ID of the resource
     * @param string $month Month in the format YYYY-MM
     * @return \Illuminate\Http\Response
     */
    public function getLeavesByMonth($resourceId, $month)
    {
        $start = Carbon::parse($month . '-01');
        $end = $start->copy()->endOfMonth();

        $leaves = Leave::where('resource_id', $resourceId)
            ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('end_date', [$start, $end]);
            })
            ->get()
            ->map(function($l) use (&$totalLeaveDays) {
                $dates = [];
                $current = Carbon::parse($l->start_date);
                $endDate = Carbon::parse($l->end_date);
                while($current <= $endDate) {
                    $dates[] = $current->format('Y-m-d');
                    $current->addDay();
                }

                $totalLeaveDays += (float) $l->number_of_days;

                return [
                    'type' => $l->type,
                    'hours_impacted' => $l->hours_impacted,
                    'dates' => $dates,
                    'number_of_days' => $l->number_of_days,
                ];
            });
  
        // Flatten dates
        $allDates = [];
        foreach($leaves as $l) {
            foreach($l['dates'] as $d) {
                $allDates[] = $d;
            }
        }

        // return response()->json(
        //     $allDates
        // );

        return response()->json([
            'dates' => $allDates,
            'total_leave_days' => $totalLeaveDays,
            'leaves' => $leaves
        ]);
    }

    /**
     * Retrieves all allocations for a given resource and month.
     * If the project ID is provided in the request, only allocations for that project will be returned.
     * 
     * @param \Illuminate\Http\Request $request
     * @param int $resourceId
     * @param string $month
     * @return \Illuminate\Http\Response
     */
    public function getAssignments(Request $request, $resourceId, $month)
    {
        $projectId = $request->query('project_id'); // optional

        $allocations = ResourceProjectAllocation::with('project')
            ->where('resource_id', $resourceId)
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->get();

        $data = $allocations->map(function ($alloc) use ($month) {
            // Decode months_and_hours JSON
            $monthsData = json_decode($alloc->months_and_hours ?? '[]', true);

            // Find requested month
            $monthInfo = null;
            foreach ($monthsData as $m) {
                if ($m['month'] === $month) {
                    $monthInfo = $m;
                    break;
                }
            }

            // Skip this allocation if month not found
            if (!$monthInfo) {
                return null; // will be filtered out later
            }

            // Decode daily_hours JSON for the requested month
            $dailyAlloc = [];
            $existingDaily = json_decode($alloc->daily_hours ?? '[]', true);
            foreach ($existingDaily as $day) {
                if (Carbon::parse($day['date'])->format('Y-m') === $month) {
                    $dailyAlloc[$day['date']] = (float)$day['hours'];
                }
            }

            return [
                'allocation_id'    => $alloc->id,
                'project_id'       => $alloc->project->id,
                'name'             => $alloc->project->name,
                'status'           => $alloc->project->status,
                'priority'         => $alloc->project->priority,
                'allocations'      => $dailyAlloc,
                'total_hours'      => $monthInfo['total_hours'] ?? 0,
                'available_hours'  => $monthInfo['available_hours'] ?? 0,
                'start_date'       => $alloc->project->start_date,
                'end_date'         => $alloc->project->end_date,
            ];
        })->filter(); // remove null entries

        return response()->json($data);
    }

    /**
     * Save or update allocations for one project-resource-month
     */
    public function saveAllocations(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'resource_id' => 'required|exists:resources,id',
                'project_id'  => 'required|exists:projects,id',
                'month'       => 'required|date_format:Y-m',
                'daily_hours' => 'required|json',
            ]);

            $resourceId = $validated['resource_id'];
            $projectId  = $validated['project_id'];
            $month      = $validated['month'];
            $newDailyHours = json_decode($request->daily_hours, true) ?: [];

            $allocation = ResourceProjectAllocation::where('resource_id', $resourceId)
                ->where('project_id', $projectId)
                ->first();

            // --- Validation: Project Check ---
            $project = Project::find($projectId);
            if (!$project) throw new Exception("Project not found");

            // We will filter out-of-range dates AFTER merging with existing data
            // to allow clearing (setting to 0) any previously saved out-of-range allocations.

            // 2. Max Hours Check (Approximate: Check if this single resource's addition pushes project over limit?)
            // A strict global check requires summing ALL allocations for this project.
            // Let's implement a strict check.
            $currentTotalAllocated = ResourceProjectAllocation::where('project_id', $projectId)
                ->where('resource_id', '!=', $resourceId) // Exclude current resource to re-add later
                ->get()
                ->sum(function($a) {
                    $months = json_decode($a->months_and_hours ?? '[]', true);
                    return array_sum(array_column($months, 'allocated_hours'));
                });

            // Calculate new allocation for THIS resource from the request + existing days merged
            // (We need the merged result first to know the new total for this resource)

            if (!$allocation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Allocation record not found for this resource and project.',
                ], 404);
            }

            // --- Merge daily_hours ---
            $existingDaily = json_decode($allocation->daily_hours ?? '[]', true) ?: [];
            $dailyMap = [];
            foreach ($existingDaily as $d) $dailyMap[$d['date']] = (float)($d['hours'] ?? 0);
            foreach ($newDailyHours as $d) if(isset($d['date'])) $dailyMap[$d['date']] = (float)($d['hours'] ?? 0);

            $updatedDaily = [];
            $dates = array_keys($dailyMap);
            sort($dates);
            foreach ($dates as $dt) {
                $hours = (float)$dailyMap[$dt];
                // Keep all entries (including 0 hrs) as long as they are within project date range
                if ($dt >= $project->start_date && $dt <= $project->end_date) {
                    $updatedDaily[] = ['date' => $dt, 'hours' => $hours];
                }
            }

            // --- STRICT MAX HOURS CHECK ---
            // Sum hours for this resource
            $thisResourceTotal = array_sum(array_column($updatedDaily, 'hours'));
            
            if (($currentTotalAllocated + $thisResourceTotal) > $project->total_hours) {
                 return response()->json([
                    'success' => false,
                    'message' => "Allocation exceeds project total hours limit ({$project->total_hours} hrs)."
                ], 422);
            }

            // --- Recalculate months_and_hours based on daily_hours ---
            $monthsGrouped = [];
            foreach($updatedDaily as $d){
                $m = substr($d['date'],0,7);
                if(!isset($monthsGrouped[$m])) $monthsGrouped[$m] = [];
                $monthsGrouped[$m][] = $d;
            }

            $monthsData = [];
            foreach($monthsGrouped as $m=>$days){
                $totalHours = $this->getMaxHoursMonth($m, $this->dailyWorkingHours);
                $allocated = array_sum(array_map(fn($d)=> (float)$d['hours'], $days));
                $monthsData[] = [
                    'month' => $m,
                    'total_hours' => $totalHours,
                    'allocated_hours' => $allocated,
                    'available_hours' => max(0,$totalHours-$allocated),
                    'utilization' => $totalHours ? round($allocated/$totalHours*100,1):0,
                    'leave_days' => 0,
                ];
            }

            $allocation->update([
                'daily_hours'=> json_encode($updatedDaily),
                'months_and_hours'=> json_encode($monthsData),
            ]);

            DB::commit();

            return response()->json([
                'success'=>true,
                'message'=>'Allocation updated successfully.',
                'data'=>$allocation->fresh(),
            ]);

        } catch (Exception $e){
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>'Error saving allocation: '.$e->getMessage()],500);
        }
    }

    /**
     * Delete (soft delete) an allocation record
     */
    public function deleteAllocation($id)
    {
        $allocation = ResourceProjectAllocation::find($id);

        if (!$allocation) {
            return response()->json(['success' => false, 'message' => 'Allocation not found.'], 404);
        }

        $allocation->delete();

        return response()->json(['success' => true, 'message' => 'Allocation deleted successfully.']);
    }

    /**
     * Save multiple allocations for a resource in one request
     */
    public function saveBulkAllocations(Request $request)
    {
        $request->validate([
            'resource_id' => 'required|exists:resources,id',
            'allocations' => 'required|json',
        ]);

        $resourceId = $request->resource_id;
        $allocations = json_decode($request->allocations, true) ?: [];

        DB::beginTransaction();
        try {
            foreach ($allocations as $proj) {
                $projectId = $proj['project_id'] ?? null;
                $newDailyHours = $proj['daily_hours'] ?? [];

                if (!$projectId || !is_array($newDailyHours)) continue;

                // --- Validation: Project Check ---
                $project = Project::find($projectId);
                if (!$project) continue;

                // We will filter out-of-range dates AFTER merging with existing data
                // to allow clearing (setting to 0) any previously saved out-of-range allocations.

                // Fetch or create allocation record
                $allocation = ResourceProjectAllocation::firstOrCreate([
                    'resource_id' => $resourceId,
                    'project_id' => $projectId,
                ], [
                    'daily_hours'      => json_encode([]),
                    'months_and_hours' => json_encode([]),
                    'total_hours'      => 0,
                ]);

                // --- Merge daily_hours ---
                $existingDaily = json_decode($allocation->daily_hours ?? '[]', true) ?: [];
                $dailyMap = [];

                foreach ($existingDaily as $d) {
                    $dailyMap[$d['date']] = (float)($d['hours'] ?? 0);
                }

                foreach ($newDailyHours as $d) {
                    if (isset($d['date'])) {
                        $dailyMap[$d['date']] = (float)($d['hours'] ?? 0);
                    }
                }

                $updatedDaily = [];
                $dates = array_keys($dailyMap);
                sort($dates);
                foreach ($dates as $dt) {
                    $hours = (float)$dailyMap[$dt];
                    // Keep all entries (including 0 hrs) as long as they are within project date range
                    if ($dt >= $project->start_date && $dt <= $project->end_date) {
                        $updatedDaily[] = ['date' => $dt, 'hours' => $hours];
                    }
                }

                // --- STRICT MAX HOURS CHECK ---
                $thisResourceTotal = array_sum(array_column($updatedDaily, 'hours'));

                 // Sum OTHER resources' allocations for this project
                $currentTotalAllocated = ResourceProjectAllocation::where('project_id', $projectId)
                    ->where('resource_id', '!=', $resourceId)
                    ->get()
                    ->sum(function($a) {
                         $months = json_decode($a->months_and_hours ?? '[]', true);
                         return array_sum(array_column($months, 'allocated_hours'));
                    });

                if (($currentTotalAllocated + $thisResourceTotal) > $project->total_hours) {
                     // Rollback and return error
                     DB::rollBack();
                     return response()->json([
                        'success' => false,
                        'message' => "Allocation exceeds project limit ({$project->total_hours} hrs) for project: {$project->name}."
                    ], 422);
                }

                $allocation->daily_hours = json_encode($updatedDaily);

                // --- Recalculate months_and_hours with working days ---
                $monthsGrouped = [];
                foreach ($updatedDaily as $d) {
                    $m = substr($d['date'], 0, 7); // 'YYYY-MM'
                    if (!isset($monthsGrouped[$m])) $monthsGrouped[$m] = [];
                    $monthsGrouped[$m][] = $d;
                }

                $monthsData = [];
                foreach ($monthsGrouped as $m => $days) {
                    $totalHours = $this->getMaxHoursMonth($m, $this->dailyWorkingHours); // correct total hours
                    $allocated = array_sum(array_map(fn($d) => (float)$d['hours'], $days));

                    $monthsData[] = [
                        'month' => $m,
                        'total_hours' => $totalHours,
                        'allocated_hours' => $allocated,
                        'available_hours' => max(0, $totalHours - $allocated),
                        'utilization' => $totalHours ? round($allocated / $totalHours * 100, 1) : 0,
                        'leave_days' => 0,
                    ];
                }

                // Sort months chronologically
                usort($monthsData, fn($a, $b) => strcmp($a['month'], $b['month']));

                // Save allocation
                $allocation->update([
                    'daily_hours'      => json_encode($updatedDaily),
                    'months_and_hours' => json_encode($monthsData),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Allocations updated successfully.',
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error updating allocations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Save a bulk of allocations for multiple resources at once.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function saveMegaBulkAllocations(Request $request)
    {
        $request->validate(['data'=>'required|json']);
        $data = json_decode($request->data,true);
        if(!is_array($data)) return response()->json(['success'=>false,'message'=>'Invalid data format.'],422);

        DB::beginTransaction();
        try{
            foreach($data as $resourceData){
                $resourceId = $resourceData['resource_id'] ?? null;
                $month = $resourceData['month'] ?? null;
                $allocations = $resourceData['allocations'] ?? [];

                if(!$resourceId || !$month || !is_array($allocations)) continue;

                $resource = Resource::find($resourceId);
                if(!$resource) continue;

                foreach($allocations as $proj){
                    $projectId = $proj['project_id'] ?? null;
                    $newDailyHours = $proj['daily_hours'] ?? [];
                    if(!$projectId || !is_array($newDailyHours)) continue;

                    // --- Validation: Project Check ---
                    $project = Project::find($projectId);
                    if (!$project) continue;

                    // We will filter out-of-range dates AFTER merging with existing data
                    // to allow clearing (setting to 0) any previously saved out-of-range allocations.

                    $allocation = ResourceProjectAllocation::where('resource_id',$resourceId)
                        ->where('project_id',$projectId)->first();
                    if(!$allocation) continue;

                    // --- Merge daily_hours for selected month ---
                    $existingDaily = json_decode($allocation->daily_hours ?? '[]', true);
                    $dailyMap = [];
                    foreach($existingDaily as $d) $dailyMap[$d['date']] = (float)($d['hours'] ?? 0);
                    foreach($newDailyHours as $d) if(isset($d['date'])) $dailyMap[$d['date']] = (float)($d['hours'] ?? 0);

                    $updatedDaily = [];
                    $dates = array_keys($dailyMap);
                    sort($dates);
                    foreach ($dates as $dt) {
                        $hours = (float)$dailyMap[$dt];
                        // Keep all entries (including 0 hrs) as long as they are within project date range
                        if ($dt >= $project->start_date && $dt <= $project->end_date) {
                             $updatedDaily[] = ['date' => $dt, 'hours' => $hours];
                        }
                    }

                    // --- STRICT MAX HOURS CHECK ---
                    $thisResourceTotal = array_sum(array_column($updatedDaily, 'hours'));
                    
                    $currentTotalAllocated = ResourceProjectAllocation::where('project_id', $projectId)
                        ->where('resource_id', '!=', $resourceId)
                        ->get()
                        ->sum(function($a) {
                             $months = json_decode($a->months_and_hours ?? '[]', true);
                             return array_sum(array_column($months, 'allocated_hours'));
                        });

                    if (($currentTotalAllocated + $thisResourceTotal) > $project->total_hours) {
                         DB::rollBack();
                         return response()->json([
                            'success' => false,
                            'message' => "Allocation exceeds project limit ({$project->total_hours} hrs) for project: {$project->name}."
                        ], 422);
                    }

                    // --- Recalculate months_and_hours ---
                    $monthsGrouped = [];
                    foreach($updatedDaily as $d){
                        $m = substr($d['date'],0,7);
                        if(!isset($monthsGrouped[$m])) $monthsGrouped[$m] = [];
                        $monthsGrouped[$m][] = $d;
                    }

                    $monthsData = [];
                    foreach($monthsGrouped as $m=>$days){
                        $totalHours = $this->getMaxHoursMonth($m,$this->dailyWorkingHours);
                        $allocated = array_sum(array_map(fn($d)=>(float)$d['hours'],$days));
                        $monthsData[] = [
                            'month'=>$m,
                            'total_hours'=>$totalHours,
                            'allocated_hours'=>$allocated,
                            'available_hours'=>max(0,$totalHours-$allocated),
                            'utilization'=>$totalHours ? round($allocated/$totalHours*100,1):0,
                            'leave_days'=>0,
                        ];
                    }

                    $allocation->update([
                        'daily_hours'=>json_encode($updatedDaily),
                        'months_and_hours'=>json_encode($monthsData),
                    ]);
                }
            }

            DB::commit();
            return response()->json(['success'=>true,'message'=>'All allocations updated successfully!']);
        }catch(Exception $e){
            DB::rollBack();
            return response()->json(['success'=>false,'message'=>'Error updating allocations: '.$e->getMessage()],500);
        }
    }

    /**
     * Store a newly created project in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:2|max:50',
                'total_hours' => 'required|numeric|min:1|max:10000',
                'description' => 'nullable|string|min:2|max:5000',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'project_manager_id' => 'required|exists:resources,id',
                'resource_ids' => 'required|array|min:1',
                'resource_ids.*' => 'exists:resources,id',
                'status' => 'nullable|string',
                'priority' => 'nullable|integer',
                'is_billable' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Create project
            $project = Project::create([
                'name' => $request->name,
                'total_hours' => $request->total_hours,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'project_manager_id' => $request->project_manager_id,
                'resource_ids' => json_encode($request->resource_ids),
                'status' => $request->status ?? 'active',
                'priority' => $request->priority,
                'is_billable' => $request->is_billable,
                'created_at' => Carbon::now(),
            ]);

            // --- Allocation logic for each selected resource ---
            $month = Carbon::parse($request->start_date)->format('Y-m');
            $maxHoursMonth = $this->getMaxHoursMonth($month, $this->dailyWorkingHours);

            foreach ($request->resource_ids as $resourceId) {
                $resource = Resource::find($resourceId);
                if (!$resource) continue;

                // Calculate working days in that month (excluding weekends)
                [$year, $m] = explode('-', $month);
                $daysInMonth = Carbon::createFromDate($year, $m, 1)->daysInMonth;

                $holidays = DB::table('holidays')->pluck('date')->toArray(); // global holidays

                // Convert project start/end dates
                $projectStart = Carbon::parse($request->start_date);
                $projectEnd   = Carbon::parse($request->end_date);

                // Generate daily_hours for project duration (excluding weekends & holidays)
                $dailyHours = [];
                $monthData = [];
                $monthsAndHours = [];
                $months = []; 
                $period = CarbonPeriod::create($projectStart, $projectEnd);
                foreach ($period as $date) {
                    if ($date->isWeekend() || in_array($date->toDateString(), $holidays)) {
                        continue;
                    }
                    $dateStr = $date->toDateString();
                    $dailyHours[] = [
                        'date' => $dateStr,
                        'hours' => 0
                    ];
                    $m = $date->format('Y-m');
                    if (!isset($monthData[$m])) $monthData[$m] = ['allocated_hours' => 0, 'project_days' => 0];
                    $monthData[$m]['project_days']++;
                    if (!in_array($m, $months)) $months[] = $m;
                    // if (!$date->isWeekend() & !in_array($date->toDateString(), $holidays)) {
                    //     $dailyHours[] = [
                    //         'date' => $date->toDateString(),
                    //         'hours' => 0
                    //     ];

                    //     $m = $date->format('Y-m');
                    //     if (!isset($monthData[$m])) $monthData[$m] = ['allocated_hours'=>0,'total_days'=>0];
                    //     $monthData[$m]['total_days']++;
                    // }
                }

                // Generate months_and_hours array
                // foreach ($monthData as $month => $data) {
                //     $totalHours = $data['total_days'] * $this->dailyWorkingHours;
                //     $monthsAndHours[] = [
                //         'month' => $month,
                //         'total_hours' => $totalHours,
                //         'available_hours' => $totalHours,
                //         'allocated_hours' => $data['allocated_hours'],
                //     ];
                // }
                // --- Build months_and_hours array with leave_days & utilization ---
                $monthsAndHours = [];
                foreach ($monthData as $mth => $data) {
                    // $totalHours = $data['total_days'] * $this->dailyWorkingHours;
                    // $allocatedHours = $data['allocated_hours'];
                    // $availableHours = $totalHours - $allocatedHours;

                    // // Leave days — integrate later from your leave table if needed
                    // $leaveDays = 0; 

                    // // Utilization calculation (%)
                    // $utilization = $totalHours > 0 ? round(($allocatedHours / $totalHours) * 100, 1) : 0;

                    // $monthsAndHours[] = [
                    //     'month' => $month,
                    //     'leave_days' => $leaveDays,
                    //     'total_hours' => $totalHours,
                    //     'utilization' => $utilization,
                    //     'allocated_hours' => $allocatedHours,
                    //     'available_hours' => $availableHours,
                    // ];
                    $totalHours = $this->getMaxHoursMonth($mth, $this->dailyWorkingHours); // full month capacity
                    $allocatedHours = $data['allocated_hours'];
                    $availableHours = max(0, $totalHours - $allocatedHours);
                    $utilization = $totalHours ? round(($allocatedHours / $totalHours) * 100, 1) : 0;
                    $leaveDays = 0; // placeholder; you can compute real leave days per resource/month

                    $monthsAndHours[] = [
                        'month' => $mth,
                        'leave_days' => $leaveDays,
                        'total_hours' => $totalHours,
                        'utilization' => $utilization,
                        'allocated_hours' => $allocatedHours,
                        'available_hours' => $availableHours,
                    ];
                }

                // for ($day = 1; $day <= $daysInMonth; $day++) {
                //     $date = Carbon::createFromDate($year, $m, $day);
                //     if (!$date->isWeekend()) {
                //         $dailyHours[] = ['date' => $date->toDateString(), 'hours' => 0]; // <-- hours always 0
                //     }
                // }

                $allocatedHours = 0; // <-- initially 0
                $availableHours = $maxHoursMonth; // <-- initially total_hours

                // --- Handle allocations ---
                $currentResourceIds = $request->resource_ids;

                // Soft-delete allocations for removed resources
                ResourceProjectAllocation::where('project_id', $project->id)
                    ->whereNotIn('resource_id', $currentResourceIds)
                    ->delete(); // soft delete (requires SoftDeletes)

                foreach ($period as $date) {
                    $m = $date->format('Y-m');
                    if (!in_array($m, $months)) $months[] = $m;
                }

                // Update or create allocations for current resources
                // foreach ($currentResourceIds as $resourceId) {
                ResourceProjectAllocation::updateOrCreate(
                    [
                        'resource_id' => $resourceId,
                        'project_id' => $project->id,
                        // 'month' => $month,
                    ],
                    [
                        'months_and_hours' => json_encode($monthsAndHours),
                        'month' => json_encode($months),
                        'total_hours' => $this->getMaxHoursMonth($request->start_date ? Carbon::parse($request->start_date)->format('Y-m') : $months[0] ?? null, $this->dailyWorkingHours),
                        'available_hours' => array_sum(array_column($monthsAndHours, 'available_hours')) ?? 0,
                        'allocated_hours' => array_sum(array_column($monthsAndHours, 'allocated_hours')) ?? 0,
                        'daily_hours' => json_encode($dailyHours),
                    ]
                );
                // }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project and allocations created successfully!',
                'project' => $project,
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Project Store Error: ' . $e->getMessage() . ' Line: ' . $e->getLine());
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $project = Project::findOrFail($id);

        return response()->json([
            'id' => $project->id,
            'name' => $project->name,
            'description' => $project->description,
            'start_date' => $project->start_date,
            'end_date' => $project->end_date,
            'project_manager_id' => $project->project_manager_id,
            'resource_ids' => json_decode($project->resource_ids, true),
            'total_hours' => $project->total_hours,
            'status' => $project->status,
            'priority' => $project->priority,
            'is_billable' => (bool)$project->is_billable,
        ]);
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $project = Project::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|min:2|max:50',
                'total_hours' => 'required|numeric|min:1|max:10000',
                'description' => 'nullable|string|min:2|max:5000',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'project_manager_id' => 'required|exists:resources,id',
                'resource_ids' => 'required|array|min:1',
                'resource_ids.*' => 'exists:resources,id',
                'status' => 'nullable|string',
                'priority' => 'nullable|integer',
                'is_billable' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Detect date change
            $dateChanged = (
                $request->start_date !== $project->start_date ||
                $request->end_date !== $project->end_date
            );

            // Update project master data
            $project->update([
                'name' => $request->name,
                'total_hours' => $request->total_hours,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'project_manager_id' => $request->project_manager_id,
                'resource_ids' => json_encode($request->resource_ids),
                'status' => $request->status ?? 'active',
                'priority' => $request->priority,
                'is_billable' => $request->boolean('is_billable'),
                'updated_at' => Carbon::now(),
            ]);

            // Prepare date range
            $holidays = DB::table('holidays')->pluck('date')->toArray();

            $projectStart = Carbon::parse($request->start_date);
            $projectEnd   = Carbon::parse($request->end_date);
            $period = CarbonPeriod::create($projectStart, $projectEnd);

            $currentResourceIds = $request->resource_ids;

            // Remove allocations of unassigned resources
            ResourceProjectAllocation::where('project_id', $project->id)
                ->whereNotIn('resource_id', $currentResourceIds)
                ->delete();

            foreach ($currentResourceIds as $resourceId) {

                // DO NOT overwrite existing allocation
                $allocation = ResourceProjectAllocation::withTrashed()->firstOrNew([
                    'resource_id' => $resourceId,
                    'project_id'  => $project->id,
                ]);

                $isRestored = false;
                if ($allocation->trashed()) {
                    $allocation->restore();
                    $isRestored = true;
                }

                $isNewAllocation = !$allocation->exists;

                // Initialize only for NEW resource
                if ($isNewAllocation) {
                    $allocation->daily_hours = json_encode([]);
                    $allocation->months_and_hours = json_encode([]);
                }

                // If existing resource AND dates didn't change AND not just restored → preserve as is
                if (!$isNewAllocation && !$dateChanged && !$isRestored) {
                    continue;
                }

                $existingDaily = json_decode($allocation->daily_hours ?? '[]', true) ?: [];
                $existingMap = collect($existingDaily)->pluck('hours', 'date')->toArray();

                $dailyHours = [];

                foreach ($period as $date) {
                    $dateStr = $date->toDateString();

                    if ($date->isWeekend() || in_array($dateStr, $holidays)) {
                        continue;
                    }

                    $dailyHours[] = [
                        'date'  => $dateStr,
                        'hours' => $existingMap[$dateStr] ?? 0, // preserve hours
                    ];
                }

                // Recalculate months_and_hours
                $monthData = [];

                foreach ($dailyHours as $d) {
                    $month = substr($d['date'], 0, 7);

                    if (!isset($monthData[$month])) {
                        $monthData[$month] = [
                            'allocated_hours' => 0,
                            'project_days' => 0
                        ];
                    }

                    $monthData[$month]['allocated_hours'] += (float)$d['hours'];
                    $monthData[$month]['project_days']++;
                }

                $monthsAndHours = [];

                foreach ($monthData as $month => $data) {
                    $totalHours = $this->getMaxHoursMonth($month, $this->dailyWorkingHours);
                    $allocated = $data['allocated_hours'];

                    $monthsAndHours[] = [
                        'month' => $month,
                        'leave_days' => 0,
                        'total_hours' => $totalHours,
                        'utilization' => $totalHours
                            ? round(($allocated / $totalHours) * 100, 1)
                            : 0,
                        'allocated_hours' => $allocated,
                        'available_hours' => max(0, $totalHours - $allocated),
                    ];
                }

                $allocation->daily_hours = json_encode($dailyHours);
                $allocation->months_and_hours = json_encode($monthsAndHours);
                $allocation->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully!',
                'date_changed' => $dateChanged,
            ], 200);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Project Update Error: ' . $e->getMessage() . ' Line: ' . $e->getLine());

            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }
}
