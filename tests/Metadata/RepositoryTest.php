<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Metadata\Repository;

class RepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $r = new Repository('Class\Name\SpaceRepository');

        $this->assertSame('Class\Name\SpaceRepository', (string) $r);
    }
}
