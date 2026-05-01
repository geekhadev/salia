<?php

namespace Tests\Unit;

use App\Ai\Tools\ListProducts;
use Tests\TestCase;

class ListProductsToolTest extends TestCase
{
    public function test_description_instructs_tool_use_for_catalog_requests(): void
    {
        $description = (string) (new ListProducts)->description();

        $this->assertNotSame('', trim($description));
        $this->assertStringContainsStringIgnoringCase('ListProducts', $description);
        $this->assertStringContainsStringIgnoringCase('menú', $description);
        $this->assertStringContainsStringIgnoringCase('MUST', $description);
    }
}
