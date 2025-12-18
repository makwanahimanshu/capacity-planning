<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    protected $fillable = [
        'resource_id','type','start_date','end_date',
        'number_of_days','hours_impacted','remark'
    ];

    protected $casts = [
        'resource_id'     => 'integer',
        'hours_impacted'  => 'integer',
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id');
    }
}
