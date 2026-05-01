<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AgentConversationStringUserIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_conversations_accept_twilio_style_user_ids(): void
    {
        $conversationId = (string) Str::uuid7();
        $externalUserId = 'whatsapp:+56937263654';

        DB::table('agent_conversations')->insert([
            'id' => $conversationId,
            'user_id' => $externalUserId,
            'title' => 'WhatsApp',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $row = DB::table('agent_conversations')->where('id', $conversationId)->first();

        $this->assertNotNull($row);
        $this->assertSame($externalUserId, $row->user_id);
    }
}
