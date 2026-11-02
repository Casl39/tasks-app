<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;
    protected $fillable = [
        'title',
        'description',
        'status',
        'deadline',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
        ];
    }

    public function deadlineStatus(): string
    {
        if ($this->status === 'done') {
            return 'on_track';
        }

        if ($this->deadline->isPast()) {
            return 'overdue';
        }

        if ($this->deadline->lt(now()->addHours(24))) {
            return 'due_soon';
        }

        return 'on_track';
    }
}
