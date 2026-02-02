<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'workspace_id',
        'created_by',
        'name',
        'description',
    ];

    public function board()
    {
        return $this->hasOne(Board::class);
    }

    public function workspace()
{
    return $this->belongsTo(Workspace::class);
}
}

