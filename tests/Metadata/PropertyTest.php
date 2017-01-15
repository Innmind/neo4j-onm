<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Property,
    TypeInterface
};

class PropertyTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $p = new Property(
            'foo',
            $t = $this->getMock(TypeInterface::class)
        );

        $this->assertSame('foo', $p->name());
        $this->assertSame($t, $p->type());
    }
}
