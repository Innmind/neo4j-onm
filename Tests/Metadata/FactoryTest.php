<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $f = new Factory('Class\Name\SpaceFactory');

        $this->assertSame('Class\Name\SpaceFactory', (string) $f);
    }
}
