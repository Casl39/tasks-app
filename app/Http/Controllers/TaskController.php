<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Support\Facades\Cache;
use App\Http\Requests\UpdateTaskStatusRequest;

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

    public function updateStatus(UpdateTaskStatusRequest $request, $id){
        $task = Task::findOrFail($id);
        if (! $task->canTransitionTo($request->status)) {
            return response()->json(['message' => "Нельзя перейти из {$task->status} в {$request->status}"], 422);
        }

        else {
            $task->status = $request->status;
            $task->save();
        Cache::forget("task:{$id}");
        return array_merge($task->toArray(), ['deadline_status' => $task->deadlineStatus()]);
        }
    }
}
