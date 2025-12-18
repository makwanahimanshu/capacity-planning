<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $table = 'projects';

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'project_manager_id',
        'resource_ids',
        'total_hours',
        'status',
        'priority',
        'owner_id',
        'is_billable',
    ];

    /** A project can have many allocations */
    public function allocations()
    {
        return $this->hasMany(ResourceProjectAllocation::class, 'project_id');
    }

    /** A project can have many timesheets */
    public function timesheets()
    {
        return $this->hasMany(Timesheet::class, 'project_id');
    }

    public function manager()
    {
        return $this->belongsTo(Resource::class, 'project_manager_id');
    }

    // For getting all project in dropdown
    public static function getProjectList() {
        return static::select('id', 'name')->get();
    }
}
