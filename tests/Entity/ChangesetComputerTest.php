<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Entity\ChangesetComputer,
    IdentityInterface
};
use Innmind\Immutable\{
    MapInterface,
    Map
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
            $this->createMock(IdentityInterface::class),
            $data = (new Map('string', 'mixed'))
                ->put('id', 42)
                ->put('foo', 'bar')
                ->put('rel', (new Map('string', 'mixed'))
                    ->put('prop', 'baz')
                    ->put('child', (new Map('string', 'mixed'))
                        ->put('some', 'prop')
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
                $identity = $this->createMock(IdentityInterface::class),
                (new Map('string', 'mixed'))
                    ->put('id', 42)
                    ->put('some', 'prop')
                    ->put('should', 'change')
                    ->put('another', 'value')
                    ->put('rel', (new Map('string', 'mixed'))
                        ->put('empty', 'not this time')
                        ->put('child', (new Map('string', 'mixed'))
                            ->put('content', 'foo')
                            ->put('extra', 'content')
                        )
                    )
                    ->put('rel2', (new Map('string', 'mixed'))
                        ->put('foo', 'bar')
                        ->put('child', (new Map('string', 'mixed'))
                            ->put('content', 'baz')
                        )
                    )
            )
        );

        $diff = $this->computer->compute(
            $identity,
            (new Map('string', 'mixed'))
                ->put('id', 42)
                ->put('some', 'prop')
                ->put('should', 'to')
                ->put('extra', 'value')
                ->put('rel', (new Map('string', 'mixed'))
                    ->put('child', (new Map('string', 'mixed'))
                        ->put('content', 'bar')
                    )
                )
                ->put('rel2', (new Map('string', 'mixed'))
                    ->put('foo', 'bar')
                    ->put('child', (new Map('string', 'mixed'))
                        ->put('content', 'baz')
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
}
