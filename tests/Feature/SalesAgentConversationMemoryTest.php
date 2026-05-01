<?php

namespace Tests\Feature;

use App\Ai\Agents\SalesAgent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Ai;
use Tests\TestCase;

class SalesAgentConversationMemoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversation_messages_persist_across_prompts_when_for_user_bound(): void
    {
        Ai::fakeAgent(SalesAgent::class, [
            'Primera respuesta del asistente.',
            'Segunda respuesta del asistente.',
        ]);

        $agent = (new SalesAgent)->forUser((object) [
            'id' => 'tests-sales-agent-memory-'.uniqid('', true),
        ]);

        $agent->prompt('Primer turno del usuario.');
        $agent->prompt('Segundo turno del usuario.');

        $this->assertSame(4, (int) DB::table('agent_conversation_messages')->count());
    }
}
