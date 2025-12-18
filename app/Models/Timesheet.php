<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timesheet extends Model
{
    use HasFactory;

    protected $table = 'timesheets';

    protected $fillable = [
        'resource_id',
        'project_id',
        'date',
        'actual_hours',
    ];

    /** Timesheet belongs to a resource */
    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }

    /** Timesheet belongs to a project */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
