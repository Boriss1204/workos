<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
    'workspace_id',
    'project_id',
    'user_id',
    'action',
    'details',
];

public function user()
{
    return $this->belongsTo(\App\Models\User::class);
}
}
