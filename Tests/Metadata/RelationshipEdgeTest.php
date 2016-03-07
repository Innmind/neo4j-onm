<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\RelationshipEdge;

class RelationshipEdgeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $i = new RelationshipEdge('uuid', 'UUID', 'target');

        $this->assertSame('uuid', (string) $i);
        $this->assertSame('uuid', $i->property());
        $this->assertSame('UUID', $i->type());
        $this->assertSame('target', $i->target());
    }
}
