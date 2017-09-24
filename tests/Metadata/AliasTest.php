<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Metadata\Alias;
use PHPUnit\Framework\TestCase;

class AliasTest extends TestCase
{
    public function testInterface()
    {
        $a = new Alias('foo');

        $this->assertSame('foo', (string) $a);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\DomainException
     */
    public function testThrowWhenEmptyValue()
    {
        new Alias('');
    }
}
