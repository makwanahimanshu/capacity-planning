<?php

namespace App\Http\Controllers;

use App\Models\Resource;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    /**
     * GET /resources
     * List resources with search and pagination
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 5);

        $query = Resource::with('department:id,name');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $resources = $query->paginate($perPage);

        return response()->json($resources);
    }

    /**
     * GET /resources/{id}
     * Fetch single resource for Edit Modal
     */
    public function show($id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found',
            ], 404);
        }

        $resource->total_hours    = (float) $resource->total_hours;
        $resource->leave_hours    = (float) $resource->leave_hours;
        $resource->daily_capacity = (float) $resource->daily_capacity;
        $resource->status         = (int) $resource->status;

        return response()->json([
            'success' => true,
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
            'daily_capacity' => 'required|numeric|min:0.5',
            'total_hours'    => 'nullable|numeric|min:0',
            'leave_hours'    => 'nullable|numeric|min:0',
            'status'         => 'required|in:0,1',
        ]);

        $validated['is_project_manager'] = $request->has('is_project_manager') ? 1 : 0;
        $validated['total_hours'] = $request->input('total_hours', 0);
        $validated['leave_hours'] = $request->input('leave_hours', 0);
        $validated['status'] = $request->input('status', 1);
        $validated['role'] = $request->input('role');

        $resource = Resource::create($validated);

        return response()->json([
            'success' => true,
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
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:resources,email,' . $id,
            'dept_id'   => 'required|integer',
            'role'      => 'nullable|string|max:255',
            'daily_capacity' => 'required|numeric|min:0.5',
            'total_hours'    => 'nullable|numeric|min:0',
            'leave_hours'    => 'nullable|numeric|min:0',
            'status'         => 'required|in:0,1',
        ]);

        $validated['is_project_manager'] = $request->has('is_project_manager') ? 1 : 0;
        $validated['total_hours'] = $request->input('total_hours', 0);
        $validated['leave_hours'] = $request->input('leave_hours', 0);
        $validated['status'] = $request->input('status', 1);
        $validated['role'] = $request->input('role');

        $resource->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Resource updated successfully',
            'data' => $resource
        ]);
    }

    /**
     * DELETE /resources/{id}
     * Remove a resource (soft delete)
     */
    public function destroy($id)
    {
        $resource = Resource::find($id);

        if (!$resource) {
            return response()->json([
                'success' => false,
                'message' => 'Resource not found'
            ], 404);
        }

        $resource->delete();

        return response()->json([
            'success' => true,
            'message' => 'Resource deleted successfully'
        ]);
    }
}
