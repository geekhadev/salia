<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CloseOrder;
use App\Ai\Tools\CreateOrder;
use App\Ai\Tools\ListProducts;
use Illuminate\Support\Facades\File;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

class SalesAgent implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function instructions(): string
    {
        $base = File::get(resource_path('prompts/sales-agent-instructions.md'));

        return $base;
    }

    public function tools(): iterable
    {
        $tools = [
            new ListProducts,
            new CreateOrder,
            new CloseOrder
        ];

        return $tools;
    }
}
