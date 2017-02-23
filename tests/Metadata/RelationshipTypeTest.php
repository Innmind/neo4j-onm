<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Metadata\RelationshipType;
use PHPUnit\Framework\TestCase;

class RelationshipTypeTest extends TestCase
{
    public function testInterface()
    {
        $t = new RelationshipType('FOO');

        $this->assertSame('FOO', (string) $t);
    }
}
