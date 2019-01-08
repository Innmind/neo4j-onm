<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Entity\ChangesetComputer,
    Identity,
};
use Innmind\Immutable\{
    MapInterface,
    Map,
};
use PHPUnit\Framework\TestCase;

class ChangesetComputerTest extends TestCase
{
    private $computer;

    public function setUp()
    {
        $this->computer = new ChangesetComputer;
    }

    public function testComputeWithoutSource()
    {
        $diff = $this->computer->compute(
            $this->createMock(Identity::class),
            $data = Map::of('string', 'mixed')
                ('id', 42)
                ('foo', 'bar')
                ('rel', Map::of('string', 'mixed')
                    ('prop', 'baz')
                    ('child', Map::of('string', 'mixed')
                        ('some', 'prop')
                    )
                )
        );

        $this->assertSame($diff, $data);
    }

    public function testComputeWithSource()
    {
        $this->assertSame(
            $this->computer,
            $this->computer->use(
                $identity = $this->createMock(Identity::class),
                Map::of('string', 'mixed')
                    ('id', 42)
                    ('some', 'prop')
                    ('should', 'change')
                    ('another', 'value')
                    ('rel', Map::of('string', 'mixed')
                        ('empty', 'not this time')
                        ('child', Map::of('string', 'mixed')
                            ('content', 'foo')
                            ('extra', 'content')
                        )
                    )
                    ('rel2', Map::of('string', 'mixed')
                        ('foo', 'bar')
                        ('child', Map::of('string', 'mixed')
                            ('content', 'baz')
                        )
                    )
            )
        );

        $diff = $this->computer->compute(
            $identity,
            Map::of('string', 'mixed')
                ('id', 42)
                ('some', 'prop')
                ('should', 'to')
                ('extra', 'value')
                ('rel', Map::of('string', 'mixed')
                    ('child', Map::of('string', 'mixed')
                        ('content', 'bar')
                    )
                )
                ('rel2', Map::of('string', 'mixed')
                    ('foo', 'bar')
                    ('child', Map::of('string', 'mixed')
                        ('content', 'baz')
                    )
                )
        );

        $this->assertInstanceOf(MapInterface::class, $diff);
        $this->assertSame('string', (string) $diff->keyType());
        $this->assertSame('mixed', (string) $diff->valueType());
        $this->assertSame(
            ['should', 'extra', 'rel', 'another'],
            $diff->keys()->toPrimitive()
        );
        $this->assertSame('to', $diff->get('should'));
        $this->assertSame('value', $diff->get('extra'));
        $this->assertNull($diff->get('another'));
        $this->assertInstanceOf(MapInterface::class, $diff->get('rel'));
        $this->assertSame('string', (string) $diff->get('rel')->keyType());
        $this->assertSame('mixed', (string) $diff->get('rel')->valueType());
        $this->assertSame(
            ['child', 'empty'],
            $diff->get('rel')->keys()->toPrimitive()
        );
        $this->assertNull($diff->get('rel')->get('empty'));
        $this->assertInstanceOf(
            MapInterface::class,
            $diff->get('rel')->get('child')
        );
        $this->assertSame('string', (string) $diff->get('rel')->get('child')->keyType());
        $this->assertSame('mixed', (string) $diff->get('rel')->get('child')->valueType());
        $this->assertSame(
            ['content', 'extra'],
            $diff->get('rel')->get('child')->keys()->toPrimitive()
        );
        $this->assertSame('bar', $diff->get('rel')->get('child')->get('content'));
        $this->assertNull($diff->get('rel')->get('child')->get('extra'));
    }

    public function testThrowWhenUsingInvalidSource()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type MapInterface<string, mixed>');

        $this->computer->use(
            $this->createMock(Identity::class),
            new Map('string', 'variable')
        );
    }

    public function testThrowWhenComputingInvalidTarget()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type MapInterface<string, mixed>');

        $this->computer->compute(
            $this->createMock(Identity::class),
            new Map('string', 'variable')
        );
    }
}
