<?php

namespace Tests\Unit;

use App\Ai\Tools\CreateOrder;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\ObjectSchema;
use Tests\TestCase;

class CreateOrderToolTest extends TestCase
{
    /**
     * OpenAI strict function schemas require every key in `properties` to appear in `required`
     * (nullable fields use type union with null but stay required keys).
     */
    public function test_tool_schema_lists_every_property_as_required(): void
    {
        $tool = new CreateOrder;
        $factory = new JsonSchemaTypeFactory;

        $schemaArray = (new ObjectSchema($tool->schema($factory)))->toSchema();

        $propertyKeys = array_keys($schemaArray['properties']);
        $requiredKeys = $schemaArray['required'];

        sort($propertyKeys);
        sort($requiredKeys);

        $this->assertSame($propertyKeys, $requiredKeys);
        $this->assertContains('location', $requiredKeys);
    }
}
