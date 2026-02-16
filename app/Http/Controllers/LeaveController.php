<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Leave;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * GET /leaves
     * List all leaves (optional filters: month YYYY-MM, resource_id)
     */
    public function index(Request $request)
    {
        $query = Leave::with('resource:id,name')->orderBy('start_date', 'desc');

        if ($request->filled('resource_id')) {
            $query->where('resource_id', $request->resource_id);
        }

        if ($request->filled('month')) {
            $month = $request->month;
            if (preg_match('/^\d{4}-\d{2}$/', $month)) {
                $startOfMonth = Carbon::parse($month . '-01')->startOfDay();
                $endOfMonth = Carbon::parse($month . '-01')->endOfMonth();
                $query->where('start_date', '<=', $endOfMonth)
                    ->where('end_date', '>=', $startOfMonth);
            }
        }

        $leaves = $query->get();

        return response()->json($leaves);
    }

    /**
     * POST /leaves
     * Create leave
     */
    public function store(Request $request)
    {
        $data = $this->validateLeave($request);

        [$days, $hours] = $this->calculateDaysAndHours(
            $data['start_date'],
            $data['end_date'],
            $data['duration']
        );

        Leave::create([
            'resource_id'    => $data['resource_id'],
            'type'           => $data['type'],
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'],
            'number_of_days' => $days,
            'hours_impacted' => $hours,
            'remark'         => $data['remark'] ?? null,
        ]);

        return response()->json([
            'message' => 'Leave added successfully'
        ]);
    }

    /**
     * GET /leaves/{id}
     * Get single leave
     */
    public function show($id)
    {
        $leave = Leave::with('resource:id,name')->find($id);

        if (!$leave) {
            return response()->json([
                'message' => 'Leave not found'
            ], 404);
        }

        return response()->json($leave);
    }

    /**
     * PUT /leaves/{id}
     * Update leave
     */
    public function update(Request $request, $id)
    {
        $leave = Leave::find($id);

        if (!$leave) {
            return response()->json([
                'message' => 'Leave not found'
            ], 404);
        }

        $data = $this->validateLeave($request, false);

        [$days, $hours] = $this->calculateDaysAndHours(
            $data['start_date'],
            $data['end_date'],
            $data['duration']
        );

        $leave->update([
            'resource_id'    => $data['resource_id'] ?? $leave->resource_id,
            'type'           => $data['type'],
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'],
            'number_of_days' => $days,
            'hours_impacted' => $hours,
            'remark'         => $data['remark'] ?? null,
        ]);

        return response()->json([
            'message' => 'Leave updated successfully'
        ]);
    }

    /**
     * DELETE /leaves/{id}
     */
    public function destroy($id)
    {
        $leave = Leave::find($id);

        if (!$leave) {
            return response()->json([
                'message' => 'Leave not found'
            ], 404);
        }

        $leave->delete();

        return response()->json([
            'message' => 'Leave deleted successfully'
        ]);
    }

    /**
     * Shared validation
     * Start date: any date allowed (including past). End date logic unchanged.
     */
    private function validateLeave(Request $request, $isCreate = true)
    {
        return $request->validate([
            'resource_id' => $isCreate
                ? 'required|exists:resources,id'
                : 'nullable|exists:resources,id',

            'type'        => 'required|in:sick,paid,probation',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'duration'   => 'required|in:full,half',
            'remark'      => 'nullable|string|max:255',
        ]);
    }

    /**
     * Calculate leave days & hours
     */
    private function calculateDaysAndHours($start, $end, $duration)
    {
        // Half-day leave
        if ($duration === 'half') {
            return [0.5, 4]; // 0.5 day, 4 hours
        }

        $days = Carbon::parse($start)
            ->diffInDays(Carbon::parse($end)) + 1;

        $hours = $days * 8; // daily capacity

        return [$days, $hours];
    }

    public function checkOverlap(Request $request)
    {
        $resourceId = $request->resource_id;
        $start = $request->start_date;
        $end   = $request->end_date;
        $duration = $request->duration; // full / half

        $query = Leave::where('resource_id', $resourceId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start, $end])
                ->orWhereBetween('end_date', [$start, $end])
                ->orWhere(function ($q) use ($start, $end) {
                    $q->where('start_date', '<=', $start)
                        ->where('end_date', '>=', $end);
                });
            });

        // Exclude current leave on edit
        if ($request->filled('leave_id')) {
            $query->where('id', '!=', $request->leave_id);
        }

        $existingLeaves = $query->get();

        // Calculate total leave days already applied
        $totalExistingDays = $existingLeaves->sum('number_of_days');

        // New leave days
        $newLeaveDays = $duration === 'half' ? 0.5 : 1;

        //Block only if total exceeds 1 day
        if (($totalExistingDays + $newLeaveDays) > 1) {
            return response()->json(['exists' => true]);
        }

        return response()->json(['exists' => false]);
    }

}
