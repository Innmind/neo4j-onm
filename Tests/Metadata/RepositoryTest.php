<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\Repository;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $r = new Repository('Class\Name\SpaceRepository');

        $this->assertSame('Class\Name\SpaceRepository', (string) $r);
    }
}
