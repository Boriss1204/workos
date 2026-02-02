<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceMember extends Model
{
    protected $fillable = ['workspace_id', 'user_id', 'role'];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class);
    }
}