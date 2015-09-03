<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping\Type;

use Innmind\Neo4j\ONM\Mapping\Type\JsonType;
use Innmind\Neo4j\ONM\Mapping\Property;

class JsonTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertToDatabaseValue()
    {
        $t = new JsonType;
        $p = new Property;

        $this->assertEquals(
            '{"foo":"bar"}',
            $t->convertToDatabaseValue(['foo' => 'bar'], $p)
        );
    }

    public function testConvertToPHPValue()
    {
        $t = new JsonType;
        $p = new Property;

        $this->assertEquals(
            [42],
            $t->convertToPHPValue('[42]', $p)
        );
    }

    public function testConvertToPHPValueAsAssociative()
    {
        $t = new JsonType;
        $p = new Property;
        $p->addOption('associative', true);

        $this->assertEquals(
            ['foo' => 'bar'],
            $t->convertToPHPValue('{"foo":"bar"}', $p)
        );

        $p->addOption('associative', false);
        $d = new \stdClass;
        $d->foo = 'bar';

        $this->assertEquals(
            $d,
            $t->convertToPHPValue('{"foo":"bar"}', $p)
        );
    }

    public function testConvertToPHPValueAsObjectByDefault()
    {
        $t = new JsonType;
        $p = new Property;

        $d = new \stdClass;
        $d->foo = 'bar';

        $this->assertEquals(
            $d,
            $t->convertToPHPValue('{"foo":"bar"}', $p)
        );
    }

    public function testNullable()
    {
        $t = new JsonType;
        $p = new Property;

        $this->assertSame(
            null,
            $t->convertToDatabaseValue(null, $p)
        );

        $p->setNullable(false);
        $this->assertSame(
            'null',
            $t->convertToDatabaseValue(null, $p)
        );
    }
}
