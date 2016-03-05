<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Type;

use Innmind\Neo4j\ONM\{
    Type\SetType,
    TypeInterface,
    Types
};
use Innmind\Immutable\{
    SetInterface,
    Collection,
    Set
};

class SetTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $this->assertInstanceOf(
            TypeInterface::class,
            SetType::fromConfig(new Collection([
                'inner' => 'string',
                '_types' => new Types,
            ]))
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\TypeDeclarationException
     * @expectedExceptionMessage Missing config key "inner" in type declaration
     */
    public function testThrowWhenMissingInnerType()
    {
        SetType::fromConfig(new Collection([]));
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\RecursiveTypeDeclarationException
     */
    public function testThrowWhenInnerTypeIsArray()
    {
        SetType::fromConfig(new Collection(['inner' => 'set']));
    }

    public function testIdentifiers()
    {
        $this->assertInstanceOf(SetInterface::class, SetType::identifiers());
        $this->assertSame('string', (string) SetType::identifiers()->type());
        $this->assertSame(SetType::identifiers(), SetType::identifiers());
        $this->assertSame(['set'], SetType::identifiers()->toPrimitive());
    }

    public function testForDatabase()
    {
        $t = SetType::fromConfig(new Collection([
            'inner' => 'string',
            '_types' => new Types,
        ]));

        $this->assertSame(
            ['foo'],
            $t->forDatabase((new Set('string'))->add('foo'))
        );

        $this->assertSame(
            null,
            SetType::fromConfig(new Collection([
                'nullable' => null,
                'inner' => 'string',
                '_types' => new Types,
            ]))
                ->forDatabase(null)
        );
        $this->assertSame(
            [''],
            SetType::fromConfig(new Collection([
                'nullable' => null,
                'inner' => 'string',
                '_types' => new Types,
            ]))
                ->forDatabase((new Set('string'))->add(''))
        );
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     * @expectedExceptionMessage The set must be an instance of SetInterface<string>
     */
    public function testThrowWhenInvalidType()
    {
        SetType::fromConfig(new Collection([
            'nullable' => null,
            'inner' => 'string',
            '_types' => new Types,
        ]))
            ->forDatabase(['']);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     * @expectedExceptionMessage The set must be an instance of SetInterface<string>
     */
    public function testThrowWhenInvalidSetType()
    {
        SetType::fromConfig(new Collection([
            'nullable' => null,
            'inner' => 'string',
            '_types' => new Types,
        ]))
            ->forDatabase(new Set('int'));
    }

    public function testFromDatabase()
    {
        $t = SetType::fromConfig(new Collection([
            'inner' => 'string',
            '_types' => new Types,
        ]));

        $this->assertInstanceOf(SetInterface::class, $t->fromDatabase(['foo']));
        $this->assertSame('string', (string) $t->fromDatabase(['foo'])->type());
        $this->assertSame(['foo'], $t->fromDatabase(['foo'])->toPrimitive());
        $this->assertInstanceOf(SetInterface::class, $t->fromDatabase([null]));
        $this->assertSame('string', (string) $t->fromDatabase([null])->type());
        $this->assertSame([''], $t->fromDatabase([null])->toPrimitive());

        $t = SetType::fromConfig(new Collection([
            'nullable' => null,
            'inner' => 'string',
            '_types' => new Types,
        ]));

        $this->assertInstanceOf(SetInterface::class, $t->fromDatabase([null]));
        $this->assertSame('string', (string) $t->fromDatabase([null])->type());
        $this->assertSame([''], $t->fromDatabase([null])->toPrimitive());
    }
}
