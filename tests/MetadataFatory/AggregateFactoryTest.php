<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\MetadataFactory;

use Innmind\Neo4j\ONM\{
    MetadataFactory\AggregateFactory,
    MetadataFactoryInterface,
    Metadata\Aggregate,
    Type\StringType,
    Type\DateType,
    Types
};
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class AggregateFactoryTest extends TestCase
{
    private $factory;

    public function setUp()
    {
        $this->factory = new AggregateFactory(new Types);
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            MetadataFactoryInterface::class,
            $this->factory
        );
    }

    public function testMake()
    {
        $ar = $this->factory->make((new Map('string', 'mixed'))
            ->put('class', 'Image')
            ->put('alias', 'I')
            ->put('repository', 'ImageRepository')
            ->put('factory', 'ImageFactory')
            ->put('labels', ['Image'])
            ->put('identity', [
                'property' => 'uuid',
                'type' => 'UUID',
            ])
            ->put('properties', [
                'url' => [
                    'type' => 'string',
                ],
            ])
            ->put('children', [
                'rel' => [
                    'class' => 'DescriptionOf',
                    'type' => 'DESCRIPTION_OF',
                    'properties' => [
                        'created' => [
                            'type' => 'date',
                        ],
                    ],
                    'child' => [
                        'property' => 'description',
                        'class' => 'Description',
                        'labels' => ['Description'],
                        'properties' => [
                            'content' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ])
        );

        $this->assertInstanceOf(Aggregate::class, $ar);
        $this->assertSame('Image', (string) $ar->class());
        $this->assertSame('I', (string) $ar->alias());
        $this->assertSame('ImageRepository', (string) $ar->repository());
        $this->assertSame('ImageFactory', (string) $ar->factory());
        $this->assertSame(['Image'], $ar->labels()->toPrimitive());
        $this->assertSame('uuid', $ar->identity()->property());
        $this->assertSame('UUID', $ar->identity()->type());
        $this->assertInstanceOf(
            StringType::class,
            $ar->properties()->get('url')->type()
        );
        $this->assertSame(1, $ar->children()->count());
        $vo = $ar->children()->get('rel');
        $this->assertSame('Description', (string) $vo->class());
        $this->assertSame(['Description'], $vo->labels()->toPrimitive());
        $this->assertInstanceOf(
            StringType::class,
            $vo->properties()->get('content')->type()
        );
        $rel = $vo->relationship();
        $this->assertSame('description', $rel->childProperty());
        $this->assertSame('rel', $rel->property());
        $this->assertSame('DescriptionOf', (string) $rel->class());
        $this->assertSame('DESCRIPTION_OF', (string) $rel->type());
        $this->assertInstanceOf(
            DateType::class,
            $rel->properties()->get('created')->type()
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidConfigMap()
    {
        $this->factory->make(new Map('string', 'variable'));
    }
}
