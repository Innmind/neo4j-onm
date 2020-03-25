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
            $properties = Map::of('string', 'string'),
            $parameters = Map::of('string', 'mixed')
        );

        $this->assertSame($properties, $match->properties());
        $this->assertSame($parameters, $match->parameters());
    }

    public function testMerge()
    {
        $match = new PropertiesMatch(
            (Map::of('string', 'string'))->put('foo', 'bar'),
            (Map::of('string', 'mixed'))->put('bar', 'baz')
        );
        $match2 = $match->merge(
            new PropertiesMatch(
                (Map::of('string', 'string'))->put('bar', 'baz'),
                (Map::of('string', 'mixed'))->put('baz', 'foobar')
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

    public function testThrowWhenInvalidPropertyMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 1 must be of type Map<string, string>');

        new PropertiesMatch(
            Map::of('int', 'int'),
            Map::of('string', 'mixed')
        );
    }

    public function testThrowWhenInvalidParameterMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Map<string, mixed>');

        new PropertiesMatch(
            Map::of('string', 'string'),
            Map::of('string', 'variable')
        );
    }
}
