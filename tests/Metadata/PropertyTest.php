<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Property,
    TypeInterface
};
use PHPUnit\Framework\TestCase;

class PropertyTest extends TestCase
{
    public function testInterface()
    {
        $p = new Property(
            'foo',
            $t = $this->createMock(TypeInterface::class)
        );

        $this->assertSame('foo', $p->name());
        $this->assertSame($t, $p->type());
    }
}
