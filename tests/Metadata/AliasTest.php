<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Metadata\Alias;

class AliasTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $a = new Alias('foo');

        $this->assertSame('foo', (string) $a);
    }
}
