<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardColumn extends Model
{
    protected $fillable = ['board_id', 'name', 'position'];

    public function tasks()
    {
        return $this->hasMany(Task::class, 'column_id');
    }
}
