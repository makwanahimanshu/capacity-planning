<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $fillable = ['name'];

    public function resources()
    {
        return $this->hasMany(Resource::class, 'dept_id');
    }
}
