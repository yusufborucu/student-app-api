<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassroomLesson extends Model
{
    protected $table = 'classroom_lessons';

    protected $fillable = ['classroom_id', 'lesson_id'];
}
