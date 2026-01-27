<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Models\Project;
use App\Models\Resource;
use App\Models\ResourceProjectAllocation;
use Auth, Validator, DB, Exception, Log, Str;

class CapacityDashboardController extends Controller
{
    /**
     * Dashboard for Capacity Planning
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

    /**
     * Fetches resources from database for selected month/year, excludes depts 7 & 8
     * and returns the view with the resources data
     *
     * @queryParam month required The month for which resources are to be fetched
     * @queryParam year required The year for which resources are to be fetched
     */
    public function dashboard(Request $request)
    {
        $month = $request->get('month');
        $year = $request->get('year');
        
        $resources = [];
        
        if ($month && $year) {
            // Fetch resources from database for selected month/year
            $resources = Resource::whereMonth('date', $month)
                                ->whereYear('date', $year)
                                ->whereNotIn('dept_id', [7, 8]) // <-- exclude depts 7 & 8
                                ->get();
        }
        
        return view('capacity.charts', compact('resources'));
    }

    /**
     * Returns a JSON response containing a summary of resource capacity for a given month/year
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     * 
     * @throws \Illuminate\Validation\ValidationException
     */
    public function getResourceCapacity(Request $request)
    {
        $month = $request->input('month');          // Format: YYYY-MM
        $startDate = $request->input('start_date'); // Format: YYYY-MM-DD
        $endDate   = $request->input('end_date');   // Format: YYYY-MM-DD

        if (!$month && (!$startDate || !$endDate)) {
            return response()->json(['error' => 'Please provide month or date range'], 422);
        }

        $dailyWorkingHoursDefault = config('constants.daily_working_hours');

        // --- Determine reporting period ---
        if ($month) {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $end   = Carbon::createFromFormat('Y-m', $month)->endOfMonth();
        } else {
            $start = Carbon::parse($startDate);
            $end   = Carbon::parse($endDate);
        }

        // Fetch holidays in the period
        $holidays = DB::table('holidays')
            ->whereBetween('date', [$start, $end])
            ->pluck('date')
            ->toArray();

        $resourcesList = DB::table('resources')
            ->whereNotIn('dept_id', [7, 8]) // <-- exclude depts 7 & 8
            ->orderBy('name', 'asc')
            ->get();

        // Fetch allocations
        $allocations = DB::table('resource_project_allocations')
            ->select('resource_id', 'project_id', 'daily_hours', 'months_and_hours')
            ->whereNull('deleted_at')
            ->get()
            ->when($month, function ($collection) use ($month) {
                return $collection->filter(function ($alloc) use ($month) {
                    $months = json_decode($alloc->months_and_hours, true) ?? [];
                    return collect($months)->contains(fn($m) => ($m['month'] ?? null) === $month);
                });
            })
            ->groupBy('resource_id');

        $resources = [];
        $totalNetAvailable = 0;
        $totalAllocated = 0;
        $totalHoursSum = 0;

        foreach ($resourcesList as $res) {
            // $dailyCapacity = $res->daily_capacity ?? $dailyWorkingHoursDefault;
            $dailyCapacity = $dailyWorkingHoursDefault;
            $allocs = $allocations->get($res->id, collect());

            // --- Generate resource-specific leave dates ---
            $leaves = DB::table('leaves')
                ->where('resource_id', $res->id)
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start, $end])
                    ->orWhereBetween('end_date', [$start, $end]);
                })
                ->get();

            // $leaveDates = [];
            // foreach ($leaves as $leave) {
            //     $periodLeave = CarbonPeriod::create($leave->start_date, $leave->end_date);
            //     foreach ($periodLeave as $day) {
            //         $leaveDates[] = $day->toDateString();
            //     }
            // }
            /**
             * Build: date => leave_hours
             * Supports half-day & multi-day leaves
             */
            $leaveHoursByDate = [];

            foreach ($leaves as $leave) {
                $period = CarbonPeriod::create($leave->start_date, $leave->end_date);
                $perDayLeaveHours = $leave->hours_impacted / max(1, $leave->number_of_days);

                foreach ($period as $day) {
                    $dateStr = $day->toDateString();
                    $leaveHoursByDate[$dateStr] = ($leaveHoursByDate[$dateStr] ?? 0) + $perDayLeaveHours;
                }
            }

            // --- Calculate actual working days excluding weekends, holidays, and leaves ---
            // $workingDaysDates = $workingDaysDatesTotal = [];
            // $period = CarbonPeriod::create($start, $end);
            // foreach ($period as $date) {
            //     $dateStr = $date->toDateString();
            //     if (!$date->isWeekend() && !in_array($dateStr, $holidays) && !in_array($dateStr, $leaveDates)) {
            //         $workingDaysDates[] = $dateStr;
            //     }
            //     if (!$date->isWeekend()) {
            //         $workingDaysDatesTotal[] = $dateStr;
            //     }
            // }
            $workingDaysDatesTotal = [];
            $totalAvailableHours = 0;

            // $period = CarbonPeriod::create($start, $end);
            // foreach ($period as $date) {

            //     $dateStr = $date->toDateString();

            //     // Skip weekends
            //     if ($date->isWeekend()) {
            //         continue;
            //     }

            //     $workingDaysDatesTotal[] = $dateStr;

            //     // Skip holidays
            //     if (in_array($dateStr, $holidays)) {
            //         continue;
            //     }

            //     // Reduce only leave hours (not full day)
            //     $leaveHours = $leaveHoursByDate[$dateStr] ?? 0;

            //     $availableForDay = max(0, $dailyCapacity - $leaveHours);
            //     $totalAvailableHours += $availableForDay;
            // }
            foreach (CarbonPeriod::create($start, $end) as $date) {

                if ($date->isWeekend()) continue;

                $dateStr = $date->toDateString();
                $workingDaysDatesTotal[] = $dateStr;

                if (in_array($dateStr, $holidays)) continue;

                $leaveHours = $leaveHoursByDate[$dateStr] ?? 0;
                $totalAvailableHours += max(0, $dailyCapacity - $leaveHours);
            }

            // --- Calculate holiday hours ---
            $holiday_hours = 0;

            foreach ($holidays as $holiday) {
                $holidayDate = Carbon::parse($holiday);

                // Ignore weekends
                if (!$holidayDate->isWeekend()) {
                    $holiday_hours += $dailyCapacity;
                }
            }

            // --- Calculate total hours ---
            $totalHours = count($workingDaysDatesTotal) * $dailyCapacity;
            // $totalHoursIncludingHolidays = count($workingDaysDatesTotal) * $dailyCapacity;
            $totalHoursSum += $totalHours;

            // --- Calculate allocated hours ---
            $allocatedHours = 0;
            if ($month) {
                foreach ($allocs as $alloc) {
                    $monthsData = json_decode($alloc->months_and_hours, true) ?? [];
                    $monthSummary = collect($monthsData)->firstWhere('month', $month);
                    if ($monthSummary) {
                        $allocatedHours += $monthSummary['allocated_hours'] ?? 0;
                    }
                }
            } else {
                foreach ($allocs as $alloc) {
                    $dailyData = json_decode($alloc->daily_hours, true) ?? [];
                    foreach ($dailyData as $day) {
                        $dayDate = Carbon::parse($day['date']);
                        if (in_array($dayDate->toDateString(), $workingDaysDatesTotal)) {
                            $allocatedHours += $day['hours'];
                        }
                    }
                }
            }

            $resourceProjects = [];

            foreach ($allocs as $alloc) {

                $project = DB::table('projects')
                    ->where('id', $alloc->project_id)
                    ->where('status', 'active')
                    ->first();

                if (!$project) continue;

                $projectAllocatedHours = 0;

                if ($month) {
                    $monthsData = json_decode($alloc->months_and_hours, true) ?? [];
                    $monthSummary = collect($monthsData)->firstWhere('month', $month);
                    $projectAllocatedHours = $monthSummary['allocated_hours'] ?? 0;
                } else {
                    $dailyData = json_decode($alloc->daily_hours, true) ?? [];
                    foreach ($dailyData as $day) {
                        $dayDate = Carbon::parse($day['date']);
                        if (
                            $dayDate->between($start, $end) &&
                            !$dayDate->isWeekend() &&
                            !in_array($dayDate->toDateString(), $holidays)
                        ) {
                            $projectAllocatedHours += $day['hours'] ?? 0;
                        }
                    }
                }

                if ($projectAllocatedHours > 0) {
                    $resourceProjects[] = [
                        'project_name' => $project->name,
                        'role'         => $res->role ?? 'N/A',
                        'hours'        => round($projectAllocatedHours, 1),
                    ];
                }
            }

            $availableHours = max(0, $totalAvailableHours - $allocatedHours);

            $utilizationPercent = $totalAvailableHours > 0
                ? round(($allocatedHours / $totalAvailableHours) * 100, 1)
                : 0;

            $department = DB::table('departments')
                ->where('id', $res->dept_id)
                ->whereNotIn('id', [7, 8])
                ->value('name') ?? 'Unassigned';

            $resources[] = [
                'id'              => $res->id,
                'name'            => $res->name,
                'department'      => $department,
                'total_hours'     => $totalHours,
                'holiday_hours'   => $holiday_hours,
                'allocated_hours' => $allocatedHours,
                'available_hours' => $availableHours,
                'leave_hours'     => array_sum(array_column($leaves->toArray(), 'hours_impacted')),
                'utilization'     => $utilizationPercent,
                'working_days'    => count($workingDaysDatesTotal),
                'projects'        => $resourceProjects, 
            ];

            $totalNetAvailable += $availableHours;
            $totalAllocated += $allocatedHours;
            // $totalHoursSum += $totalHours;
        }

        // --- Department-wise aggregation ---
        $deptData = collect($resources)
            ->groupBy('department')
            ->map(fn($group) => $group->sum('available_hours'))
            ->toArray();

        $underUtilized = collect($resources)
            ->filter(fn($r) => $r['allocated_hours'] < $r['available_hours'])
            ->values();

        $globalUtilizationPercent = $totalHoursSum > 0
            ? round(($totalAllocated / $totalHoursSum) * 100, 1)
            : 0;

        $globalAvailabilityPercent = $totalHoursSum > 0
            ? round(($totalNetAvailable / $totalHoursSum) * 100, 1)
            : 0;

        // --- Project-wise and leave/holiday report (keep your existing logic) ---
        // ... you can reuse your project, leave, and holiday queries here ...

        // Projects in period
        $projects = DB::table('projects')
            ->where('status', 'active')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('end_date', [$start, $end])
                ->orWhere(function ($sub) use ($start, $end) {
                    $sub->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $end);
                });
            })
            ->orderBy('name', 'asc')
            ->get();

        $projectWiseData = [];

        foreach ($projects as $project) {

            $projectManager = DB::table('resources')
                ->where('id', $project->project_manager_id)
                ->where('is_project_manager', 1)
                ->whereNotIn('dept_id', [7, 8])
                ->value('name') ?? 'N/A';

            $projectAllocations = DB::table('resource_project_allocations as rpa')
                ->join('resources as r', 'rpa.resource_id', '=', 'r.id')
                ->select(
                    'rpa.resource_id',
                    'r.name',
                    'r.role',
                    'rpa.daily_hours',
                    'rpa.months_and_hours'
                )
                ->where('rpa.project_id', $project->id)
                ->whereNull('rpa.deleted_at')
                ->whereNotIn('r.dept_id', [7, 8])
                ->get();

            $resourcesData = [];
            $projectTotalAllocated = 0;


            // foreach ($projectAllocations as $alloc) {
            //     $allocatedHours = 0;

            //     // ---- MONTH MODE ----
            //     if ($month) {
            //         $monthsData = json_decode($alloc->months_and_hours, true) ?? [];
            //         $monthSummary = collect($monthsData)->firstWhere('month', $month);
            //         $allocatedHours = $monthSummary['allocated_hours'] ?? 0;
            //     }
            //     // ---- DATE RANGE MODE ----
            //     else {
            //         $dailyData = json_decode($alloc->daily_hours, true) ?? [];
            //         foreach ($dailyData as $day) {
            //             $dayDate = Carbon::parse($day['date']);
            //             if (
            //                 $dayDate->between($start, $end) &&
            //                 !$dayDate->isWeekend() &&
            //                 !in_array($dayDate->toDateString(), $holidays)
            //             ) {
            //                 $allocatedHours += $day['hours'] ?? 0;
            //             }
            //         }
            //     }

            //     if ($allocatedHours > 0) {
            //         $resourcesData[] = [
            //             'id'    => $alloc->resource_id,
            //             'name'  => $alloc->name,
            //             'role'  => $alloc->role ?? 'N/A',
            //             'hours' => $allocatedHours
            //         ];

            //         $projectTotalAllocated += $allocatedHours;
            //     }
            // }

            foreach ($projectAllocations as $alloc) {

                $allocatedHours = 0;
                $duration = '-';
                $weeklyHours = [];

                $dailyData = json_decode($alloc->daily_hours, true) ?? [];

                /*
                |--------------------------------------------------------------------------
                | Calculate allocated hours + weekly hours
                |--------------------------------------------------------------------------
                */
                foreach ($dailyData as $day) {

                    $dayDate = Carbon::parse($day['date']);

                    if (
                        $dayDate->between($start, $end) &&
                        !$dayDate->isWeekend() &&
                        !in_array($dayDate->toDateString(), $holidays)
                    ) {
                        $hours = $day['hours'] ?? 0;
                        $allocatedHours += $hours;

                        if ($hours > 0) {
                            $weekKey = 'Week ' . $dayDate->weekOfMonth;
                            $weeklyHours[$weekKey] = ($weeklyHours[$weekKey] ?? 0) + $hours;
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Duration calculation (day + week based)
                |--------------------------------------------------------------------------
                */
                $workedDates = collect($dailyData)
                    ->filter(fn ($d) => ($d['hours'] ?? 0) > 0)
                    ->map(fn ($d) => Carbon::parse($d['date']))
                    ->filter(fn ($d) => $d->between($start, $end));

                $workedDaysCount  = $workedDates->count();
                $workedWeeksCount = $workedDates
                    ->groupBy(fn ($d) => $d->weekOfMonth)
                    ->count();

                if ($workedDaysCount === 0) {
                    $duration = '-';
                } elseif ($workedDaysCount === 1) {
                    $duration = '1 Day';
                } elseif ($workedWeeksCount === 1) {
                    $duration = '1 Week';
                } elseif ($workedWeeksCount === 2) {
                    $duration = '2 Weeks';
                } elseif ($workedWeeksCount === 3) {
                    $duration = '3 Weeks';
                } elseif ($workedWeeksCount === 4) {
                    $duration = '4 Weeks';
                } else {
                    $duration = 'Full Month';
                }

                /*
                |--------------------------------------------------------------------------
                | Tooltip text
                |--------------------------------------------------------------------------
                */
                $weeklyTooltip = collect($weeklyHours)
                    ->map(fn ($hrs, $week) => $week . ' : ' . round($hrs, 1) . ' hrs')
                    ->implode("\n");

                /*
                |--------------------------------------------------------------------------
                | Push resource data
                |--------------------------------------------------------------------------
                */
                if ($allocatedHours > 0) {
                    $resourcesData[] = [
                        'id'             => $alloc->resource_id,
                        'name'           => $alloc->name,
                        'role'           => $alloc->role ?? 'N/A',
                        'hours'          => round($allocatedHours, 1),
                        'duration'       => $duration,
                        'weekly_tooltip' => $weeklyTooltip,
                    ];

                    $projectTotalAllocated += $allocatedHours;
                }
            }

            $projectWiseData[] = [
                'project_id'      => $project->id,
                'project_name'    => $project->name,
                'project_manager' => $projectManager,
                'resource_count'  => count($resourcesData),
                'allocated_hours' => $projectTotalAllocated,
                'resources'       => $resourcesData
            ];
        }


        // Leaves in period
        $leaves = DB::table('leaves')
            ->join('resources', 'leaves.resource_id', '=', 'resources.id')
            ->select(
                'resources.name as employee_name',
                'leaves.type',
                'leaves.start_date',
                'leaves.end_date',
                'leaves.number_of_days',
                'leaves.hours_impacted',
                'leaves.remark'
            )
            ->orderBy('resources.name', 'asc')
            ->whereNotIn('resources.dept_id', [7, 8]) // <-- exclude depts 7 & 8
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('leaves.start_date', [$start, $end])
                ->orWhereBetween('leaves.end_date', [$start, $end]);
            })
            // ->orderBy('leaves.start_date', 'asc')
            ->get();

        // Holidays in period
        $holidaysReport = DB::table('holidays')
            ->select('date', 'description')
            ->whereBetween('date', [$start, $end])
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'total_hours'          => $totalHours,
            'total_hours_sum'      => $totalHoursSum,
            'available'            => round($totalHoursSum - $totalAllocated, 1),
            'allocated'            => $totalAllocated,
            'utilization_percent'  => $globalUtilizationPercent . '%',
            'availability_percent' => $globalAvailabilityPercent,
            'departments'          => $deptData,
            'resources'            => $resources,
            'under_utilized'       => $underUtilized,
            'project_wise' => $projectWiseData,
            'leaves' => $leaves,
            'holidays' => $holidaysReport,
        ]);
    }


}
