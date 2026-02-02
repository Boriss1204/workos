<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    protected $fillable = ['name', 'owner_user_id'];

    public function members()
    {
        return $this->hasMany(WorkspaceMember::class);
    }
}

