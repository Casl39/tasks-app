<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;

class TaskController extends Controller
{
    public function show($id)
    {
        $key = "task:{$id}";
        $data = Cache::get($key);
        if ($data !== null){ return array_merge($data, ['cached' => true]);}
        $task = Task::findOrFail($id);
        $data = array_merge($task->toArray(), [
            'deadline_status' => $task->deadlineStatus(),
        ]);
        Cache::put($key, $data, 60);
        return array_merge($data, ['cached' => false]);
    }
}
