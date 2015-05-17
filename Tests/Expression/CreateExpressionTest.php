<?php

namespace Innmind\Neo4j\ONM\Tests\Expression;

use Innmind\Neo4j\ONM\Expression\CreateExpression;

class CreateExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateRepresentation()
    {
        $n = new CreateExpression('n', ['foo' => 'bar']);

        $this->assertSame(
            '(n { n_create_props })',
            (string) $n
        );
    }
}
