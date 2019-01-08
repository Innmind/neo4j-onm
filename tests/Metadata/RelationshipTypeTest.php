<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\RelationshipType,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class RelationshipTypeTest extends TestCase
{
    public function testInterface()
    {
        $type = new RelationshipType('FOO');

        $this->assertSame('FOO', (string) $type);
    }

    public function testThrowWhenEmptyType()
    {
        $this->expectException(DomainException::class);

        new RelationshipType('');
    }
}
