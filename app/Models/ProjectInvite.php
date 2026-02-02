<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectInvite extends Model
{
    protected $fillable = [
        'project_id','email','role','token','status','expires_at','invited_by'
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    protected $casts = [
    'expires_at' => 'datetime',
];

}
