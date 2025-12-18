<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Holiday;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class HolidayController extends Controller
{
    /**
     * Get all holidays sorted by date
     */
    public function index()
    {
        $holidays = Holiday::orderBy('date', 'asc')->get();
        return response()->json($holidays);
    }

    /**
     * Store new holidays (range support, skip weekends)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_range'   => 'required|string',
            'description'  => 'required|string|min:2|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $dateRange = explode(' to ', $request->date_range);
        $startDate = Carbon::parse($dateRange[0]);
        $endDate   = isset($dateRange[1]) ? Carbon::parse($dateRange[1]) : $startDate;

        $inserted = [];
        foreach (CarbonPeriod::create($startDate, $endDate) as $date) {
            if ($date->isWeekend()) continue; // skip Sat/Sun

            $exists = Holiday::whereDate('date', $date->format('Y-m-d'))->exists();
            if (!$exists) {
                $holiday = Holiday::create([
                    'date' => $date->format('Y-m-d'),
                    'description' => $request->description,
                ]);
                $inserted[] = $holiday;
            }
        }

        return response()->json([
            'message' => count($inserted) ? 'Holidays added successfully' : 'No new holidays added (may already exist)',
            'data' => $inserted
        ]);
    }

    /**
     * Update a specific holiday
     */
    // public function update(Request $request, $id)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'date'         => 'required|date',
    //         'description'  => 'required|string|min:2|max:500',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['errors' => $validator->errors()], 422);
    //     }

    //     $holiday = Holiday::find($id);
    //     if (!$holiday) {
    //         return response()->json(['message' => 'Holiday not found'], 404);
    //     }

    //     // Check duplicate date (for others)
    //     $exists = Holiday::where('date', $request->date)
    //         ->where('id', '!=', $id)
    //         ->exists();

    //     if ($exists) {
    //         return response()->json(['message' => 'Another holiday already exists on this date'], 409);
    //     }

    //     $holiday->update([
    //         'date' => $request->date,
    //         'description' => $request->description,
    //     ]);

    //     return response()->json([
    //         'message' => 'Holiday updated successfully',
    //         'data' => $holiday
    //     ]);
    // }

    public function update(Request $request, $id)
    {
        $holiday = Holiday::find($id);
        if (!$holiday) {
            return response()->json(['message' => 'Holiday not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'date'         => 'required|date',
            'description'  => 'required|string|min:2|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Only check duplicate if date changed
        if ($request->date != $holiday->date) {
            $exists = Holiday::where('date', $request->date)
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Another holiday already exists on this date'], 409);
            }
        }

        $holiday->update([
            'date' => $request->date,
            'description' => $request->description,
        ]);

        return response()->json([
            'message' => 'Holiday updated successfully',
            'data' => $holiday
        ]);
    }


    /**
     * Hard delete a holiday
     */
    public function destroy($id)
    {
        $holiday = Holiday::find($id);
        if (!$holiday) {
            return response()->json(['message' => 'Holiday not found'], 404);
        }

        $holiday->delete();
        return response()->json(['message' => 'Holiday deleted successfully']);
    }
}
