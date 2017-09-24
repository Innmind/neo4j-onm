<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Query;

use Innmind\Neo4j\ONM\Query\PropertiesMatch;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class PropertiesMatchTest extends TestCase
{
    public function testInterface()
    {
        $match = new PropertiesMatch(
            $properties = new Map('string', 'string'),
            $parameters = new Map('string', 'mixed')
        );

        $this->assertSame($properties, $match->properties());
        $this->assertSame($parameters, $match->parameters());
    }

    public function testMerge()
    {
        $match = new PropertiesMatch(
            (new Map('string', 'string'))->put('foo', 'bar'),
            (new Map('string', 'mixed'))->put('bar', 'baz')
        );
        $match2 = $match->merge(
            new PropertiesMatch(
                (new Map('string', 'string'))->put('bar', 'baz'),
                (new Map('string', 'mixed'))->put('baz', 'foobar')
            )
        );

        $this->assertInstanceOf(PropertiesMatch::class, $match2);
        $this->assertNotSame($match, $match2);
        $this->assertCount(1, $match->properties());
        $this->assertCount(1, $match->parameters());
        $this->assertCount(2, $match2->properties());
        $this->assertCount(2, $match2->parameters());
        $this->assertSame('bar', $match2->properties()->get('foo'));
        $this->assertSame('baz', $match2->properties()->get('bar'));
        $this->assertSame('baz', $match2->parameters()->get('bar'));
        $this->assertSame('foobar', $match2->parameters()->get('baz'));
    }

    /**
     * @expectedException TypeError
     * @expectedException Argument 1 must be of type MapInterface<string, string>
     */
    public function testThrowWhenInvalidPropertyMap()
    {
        new PropertiesMatch(
            new Map('int', 'int'),
            new Map('string', 'mixed')
        );
    }

    /**
     * @expectedException TypeError
     * @expectedException Argument 2 must be of type MapInterface<string, mixed>
     */
    public function testThrowWhenInvalidParameterMap()
    {
        new PropertiesMatch(
            new Map('string', 'string'),
            new Map('string', 'variable')
        );
    }
}
