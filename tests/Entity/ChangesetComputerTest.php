<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Entity;

use Innmind\Neo4j\ONM\{
    Entity\ChangesetComputer,
    IdentityInterface
};
use Innmind\Immutable\{
    Collection,
    CollectionInterface
};
use PHPUnit\Framework\TestCase;

class ChangesetComputerTest extends TestCase
{
    private $c;

    public function setUp()
    {
        $this->c = new ChangesetComputer;
    }

    public function testComputeWithoutSource()
    {
        $c = $this->c->compute(
            $this->createMock(IdentityInterface::class),
            $data = new Collection([
                'id' => 42,
                'foo' => 'bar',
                'rel' => new Collection([
                    'prop' => 'baz',
                    'child' => new Collection([
                        'some' => 'prop',
                    ]),
                ]),
            ])
        );

        $this->assertSame($c, $data);
    }

    public function testComputeWithSource()
    {
        $this->assertSame(
            $this->c,
            $this->c->use(
                $identity = $this->createMock(IdentityInterface::class),
                new Collection([
                    'id' => 42,
                    'some' => 'prop',
                    'should' => 'change',
                    'another' => 'value',
                    'rel' => new Collection([
                        'empty' => 'not this time',
                        'child' => new Collection([
                            'content' => 'foo',
                            'extra' => 'content',
                        ]),
                    ]),
                    'rel2' => new Collection([
                        'foo' => 'bar',
                        'child' => new Collection([
                            'content' => 'baz',
                        ]),
                    ]),
                ])
            )
        );

        $c = $this->c->compute(
            $identity,
            new Collection([
                'id' => 42,
                'some' => 'prop',
                'should' => 'to',
                'extra' => 'value',
                'rel' => new Collection([
                    'child' => new Collection([
                        'content' => 'bar',
                    ]),
                ]),
                'rel2' => new Collection([
                    'foo' => 'bar',
                    'child' => new Collection([
                        'content' => 'baz',
                    ]),
                ]),
            ])
        );

        $this->assertInstanceOf(CollectionInterface::class, $c);
        $this->assertSame(
            ['should', 'extra', 'rel', 'another'],
            $c->keys()->toPrimitive()
        );
        $this->assertSame('to', $c->get('should'));
        $this->assertSame('value', $c->get('extra'));
        $this->assertSame(null, $c->get('another'));
        $this->assertInstanceOf(CollectionInterface::class, $c->get('rel'));
        $this->assertSame(
            ['child', 'empty'],
            $c->get('rel')->keys()->toPrimitive()
        );
        $this->assertSame(null, $c->get('rel')->get('empty'));
        $this->assertInstanceOf(
            CollectionInterface::class,
            $c->get('rel')->get('child')
        );
        $this->assertSame(
            ['content', 'extra'],
            $c->get('rel')->get('child')->keys()->toPrimitive()
        );
        $this->assertSame('bar', $c->get('rel')->get('child')->get('content'));
        $this->assertSame(null, $c->get('rel')->get('child')->get('extra'));
    }
}
