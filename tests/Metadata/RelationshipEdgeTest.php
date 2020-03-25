<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\RelationshipEdge,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class RelationshipEdgeTest extends TestCase
{
    public function testInterface()
    {
        $edge = new RelationshipEdge('uuid', 'UUID', 'target');

        $this->assertSame('uuid', $edge->property());
        $this->assertSame('UUID', $edge->type());
        $this->assertSame('target', $edge->target());
    }

    public function testThrowWhenEmptyTarget()
    {
        $this->expectException(DomainException::class);

        new RelationshipEdge('uuid', 'UUID', '');
    }
}
