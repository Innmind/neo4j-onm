<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Metadatas,
    Metadata\ClassName,
    Metadata\Entity,
};
use PHPUnit\Framework\TestCase;

class MetadatasTest extends TestCase
{
    public function testAdd()
    {
        $meta = $this->createMock(Entity::class);
        $meta
            ->method('class')
            ->willReturn(new ClassName('bar'));
        $metadata = new Metadatas($meta);

        $this->assertSame($meta, $metadata('bar'));
    }
}
