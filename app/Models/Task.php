<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
    'board_id',
    'column_id',
    'assignee_id',
    'created_by',
    'title',
    'description',
    'priority',
    'due_date',
];

    public function column()
    {
        return $this->belongsTo(\App\Models\BoardColumn::class, 'column_id');
    }

    public function board()
    {
        return $this->belongsTo(\App\Models\Board::class, 'board_id');
    }

    public function comments()
    {
        return $this->hasMany(\App\Models\TaskComment::class);
    }

    public function attachments()
    {
        return $this->hasMany(\App\Models\TaskAttachment::class);
    }

    public function assignee()
    {
        return $this->belongsTo(\App\Models\User::class, 'assignee_id');
    }

}


