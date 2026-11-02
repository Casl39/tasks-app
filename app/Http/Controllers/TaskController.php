<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function show($id)
    {
        return ['id' => $id];
    }
}
