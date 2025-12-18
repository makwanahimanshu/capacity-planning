<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResourceProjectAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'resource_project_allocations';

    protected $fillable = [
        'resource_id',
        'project_id',
        'months_and_hours',
        'daily_hours',
        // 'month',
        // 'total_hours',
        // 'available_hours',
        // 'allocated_hours',
    ];

    // protected $casts = [
    //     'daily_hours' => 'array',
    // ];

    /** Allocation belongs to a resource */
    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    /** Allocation belongs to a project */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
