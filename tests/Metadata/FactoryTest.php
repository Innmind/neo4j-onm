<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Metadata\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $f = new Factory('Class\Name\SpaceFactory');

        $this->assertSame('Class\Name\SpaceFactory', (string) $f);
    }
}
