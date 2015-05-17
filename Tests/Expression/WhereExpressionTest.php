<?php

namespace Innmind\Neo4j\ONM\Tests\Expression;

use Innmind\Neo4j\ONM\Expression\WhereExpression;

class WhereExpressionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Where expression parameters can't be used without a key name
     */
    public function testThrowIfParametersWithoutAKey()
    {
        new WhereExpression('foo', null, ['foo' => 'bar']);
    }
}
