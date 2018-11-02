<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Metadatas,
    MetadataBuilder,
    Metadata\Alias,
    Metadata\ClassName,
    Metadata\Entity,
    Metadata\Aggregate,
    Types,
};
use Symfony\Component\Yaml\Yaml;
use PHPUnit\Framework\TestCase;

class MetadatasTest extends TestCase
{
    public function testAdd()
    {
        $meta = $this->createMock(Entity::class);
        $meta
            ->method('class')
            ->willReturn(new ClassName('bar'));
        $metadatas = new Metadatas($meta);

        $this->assertSame($meta, $metadatas->get('bar'));
    }

    public function testBuild()
    {
        $metadatas = Metadatas::build(
            new MetadataBuilder(new Types),
            [Yaml::parse(file_get_contents('fixtures/mapping.yml'))]
        );

        $this->assertInstanceOf(
            Aggregate::class,
            $metadatas->get('Image')
        );
    }
}
