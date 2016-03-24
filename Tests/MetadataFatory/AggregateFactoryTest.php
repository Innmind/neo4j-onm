<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\MetadataFactory;

use Innmind\Neo4j\ONM\{
    MetadataFactory\AggregateFactory,
    MetadataFactoryInterface,
    Metadata\Aggregate,
    Type\StringType,
    Type\DateType,
    Types
};
use Innmind\Immutable\Collection;

class AggregateFactoryTest extends \PHPUnit_Framework_TestCase
{
    private $f;

    public function setUp()
    {
        $this->f = new AggregateFactory(new Types);
    }

    public function testInterface()
    {
        $this->assertInstanceOf(
            MetadataFactoryInterface::class,
            $this->f
        );
    }

    public function testMake()
    {
        $ar = $this->f->make(new Collection([
            'class' => 'Image',
            'alias' => 'I',
            'repository' => 'ImageRepository',
            'factory' => 'ImageFactory',
            'labels' => ['Image'],
            'identity' => [
                'property' => 'uuid',
                'type' => 'UUID',
            ],
            'properties' => [
                'url' => [
                    'type' => 'string',
                ],
            ],
            'children' => [
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
            ],
        ]));

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
}
