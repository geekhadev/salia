<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CloseOrder;
use App\Ai\Tools\CreateOrder;
use App\Ai\Tools\ListProducts;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class SalesAgent implements Agent
{
    use Promptable, RemembersConversations;

    public function model(): string
    {
        return 'gpt-4o';
    }

    public function temperature(): float
    {
        return 0.2;
    }

    public function instructions(): Stringable|string
    {
        return File::get(resource_path('prompts/sales-agent-instructions.md'));
    }

    public function tools(): iterable
    {
        return [
            new ListProducts,
            new CreateOrder,
            new CloseOrder,
        ];
    }
}
