<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    /**
     * GET /resources/{id}
     * Fetch single resource for Edit Modal
     */
    public function show($id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return response()->json([
                'status' => 404,
                'message' => 'Resource not found',
            ], 404);
        }

        $resource->total_hours    = (int) $resource->total_hours;
        $resource->leave_hours    = (int) $resource->leave_hours;
        $resource->daily_capacity = (int) $resource->daily_capacity;
        $resource->status         = (int) $resource->status;

        return response()->json([
            'status' => 200,
            'data' => $resource
        ]);
    }


    /**
     * POST /resources
     * Create new resource
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:resources,email',
            'dept_id'   => 'required|integer',
            'role'      => 'nullable|string|max:255',
            'daily_capacity' => 'required|numeric|min:1',
            'total_hours'    => 'nullable|numeric|min:0',
            'leave_hours'    => 'nullable|numeric|min:0',
            'status'         => 'required|in:0,1',
        ]);

        $validated['is_project_manager'] = $request->has('is_project_manager') ? 1 : 0;
        $validated['total_hours'] = $validated['total_hours'] ? $validated['total_hours'] : 0;
        $validated['leave_hours'] = $validated['leave_hours'] ? $validated['leave_hours'] : 0;
        $validated['status'] = $validated['status'] ? $validated['status'] : 1;
        $validated['role'] = $validated['role'] ? $validated['role'] : null;

        $resource = Resource::create($validated);

        return response()->json([
            'status' => 200,
            'message' => 'Resource created successfully',
            'data' => $resource
        ]);
    }


    /**
     * PUT /resources/{id}
     * Update an existing resource
     */
    public function update(Request $request, $id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return response()->json([
                'status' => 404,
                'message' => 'Resource not found'
            ], 404);
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:resources,email,' . $id,
            'dept_id'   => 'required|integer',
            'role'      => 'nullable|string|max:255',
            'daily_capacity' => 'required|numeric|min:1',
            'total_hours'    => 'nullable|numeric|min:0',
            'leave_hours'    => 'nullable|numeric|min:0',
            'status'         => 'required|in:0,1',
        ]);

        $validated['is_project_manager'] = $request->has('is_project_manager') ? 1 : 0;
        $validated['total_hours'] = $validated['total_hours'] ? $validated['total_hours'] : 0;
        $validated['leave_hours'] = $validated['leave_hours'] ? $validated['leave_hours'] : 0;
        $validated['status'] = $validated['status'] ? $validated['status'] : 1;
        $validated['role'] = $validated['role'] ? $validated['role'] : null;

        $resource->update($validated);

        return response()->json([
            'status' => 200,
            'message' => 'Resource updated successfully',
            'data' => $resource
        ]);
    }
}
