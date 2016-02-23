<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\RelationshipType;

class RelationshipTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $t = new RelationshipType('FOO');

        $this->assertSame('FOO', (string) $t);
    }
}
