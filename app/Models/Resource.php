<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $table = 'resources';

    protected $fillable = [
        'name',
        'email',
        'dept_id',
        'total_hours',
        'is_project_manager',
        'leave_hours',
        'role',
        'daily_capacity',
        'status'
    ];

    /** A resource can have many allocations */
    public function allocations()
    {
        return $this->hasMany(ResourceProjectAllocation::class, 'resource_id');
    }

    /** A resource can log many timesheets */
    public function timesheets()
    {
        return $this->hasMany(Timesheet::class, 'resource_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'dept_id');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'resource_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'project_manager_id');
    }

    // For getting all project managers in dropdown
    public static function getProjectManagers() {
        return static::select('id', 'name', 'is_project_manager')->where('is_project_manager', 1)->get();
    }

    // For getting all project managers in dropdown
    public static function getResources() {
        return static::select('id', 'name', 'is_project_manager')
            ->whereNotIn('dept_id', [7, 8]) // Exclude departments 7 and 8
            ->get();
    }
}
