<?php

namespace App\Console\Commands;

use App\Ai\Agents\SalesAgent;
use Illuminate\Console\Command;
use function Laravel\Prompts\text;

class ChatCommand extends Command
{
    protected $signature = 'chat';

    protected $description = 'Start an interactive chat session with the AI agent';

    public function handle(): int
    {
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║     AI Agent Chat (type "exit")     ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->newLine();

        $agent = SalesAgent::make();

        while (true) {
            $message = text(
                label: 'You',
                placeholder: 'Type your message...',
            );

            if (in_array(strtolower(trim($message)), ['exit', 'quit', 'q'])) {
                $this->info('Goodbye!');
                break;
            }

            if (empty(trim($message))) {
                continue;
            }

            $this->newLine();
            $this->info('Agent is thinking...');

            try {
                $response = $agent->prompt($message);
                $this->newLine();
                $this->line('<fg=cyan>▌ ' . $response->text . '</>');
                $this->newLine();
            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
                $this->newLine();
            }
        }

        return Command::SUCCESS;
    }
}
