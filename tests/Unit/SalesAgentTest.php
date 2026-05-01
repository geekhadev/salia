<?php

namespace Tests\Unit;

use App\Ai\Agents\SalesAgent;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SalesAgentTest extends TestCase
{
    public function test_instructions_match_prompt_file(): void
    {
        $path = resource_path('prompts/sales-agent-instructions.md');

        $this->assertFileExists($path);

        $agent = new SalesAgent;
        $instructions = (string) $agent->instructions();

        $this->assertNotSame('', trim($instructions));
        $this->assertStringContainsString('Asistente de Ventas', $instructions);
        $this->assertSame(File::get($path), $instructions);
    }
}
