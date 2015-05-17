<?php

namespace Innmind\Neo4j\ONM\Tests\Expression;

use Innmind\Neo4j\ONM\Expression\UpdateExpression;

class UpdateExpressionTest extends \PHPUnit_Framework_TestCase
{
    public function testUpdateRepresentation()
    {
        $u = new UpdateExpression('n', ['foo' => 'bar']);

        $this->assertSame(
            'n += { n_update_props }',
            (string) $u
        );
    }
}
