<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Repository,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    public function testInterface()
    {
        $repository = new Repository('Class\Name\SpaceRepository');

        $this->assertSame('Class\Name\SpaceRepository', (string) $repository);
    }

    public function testThrowWhenEmptyClass()
    {
        $this->expectException(DomainException::class);

        new Repository('');
    }
}
