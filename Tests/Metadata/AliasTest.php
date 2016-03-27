<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\Alias;

class AliasTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $a = new Alias('foo');

        $this->assertSame('foo', (string) $a);
    }
}
