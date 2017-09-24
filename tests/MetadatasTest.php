<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Metadatas,
    Metadata\Alias,
    Metadata\ClassName,
    Metadata\Entity
};
use PHPUnit\Framework\TestCase;

class MetadatasTest extends TestCase
{
    public function testAdd()
    {
        $meta = $this->createMock(Entity::class);
        $meta
            ->method('alias')
            ->willReturn(new Alias('foo'));
        $meta
            ->method('class')
            ->willReturn(new ClassName('bar'));
        $metadatas = new Metadatas($meta);

        $this->assertSame($meta, $metadatas->get('foo'));
        $this->assertSame($meta, $metadatas->get('bar'));
    }
}
