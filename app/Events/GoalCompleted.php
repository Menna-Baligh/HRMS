<?php

namespace App\Events;

use App\Models\Goal;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GoalCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Goal $goal)
    {
    }
}