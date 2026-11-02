<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;

class TaskController extends Controller
{
    public function show($id)
    {
        $task = Task::findOrFail($id);
        return array_merge($task->toArray(), [
            'deadline_status' => $task->deadlineStatus(),
        ]);
    }
}
