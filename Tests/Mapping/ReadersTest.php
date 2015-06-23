<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping;

use Innmind\Neo4j\ONM\Mapping\Readers;
use Innmind\Neo4j\ONM\Mapping\Reader\YamlReader;

class ReadersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidReaderTypeException
     */
    public function testThrowWhenGettingUnknownReader()
    {
        Readers::getReader('foo');
    }

    public function testAddReader()
    {
        $this->assertEquals(null, Readers::addReader('foo', new YamlReader));
        $this->assertInstanceOf('Innmind\\Neo4j\\ONM\\Mapping\\Reader\\YamlReader', Readers::getReader('foo'));
    }

    public function testGetDefaultReaders()
    {
        $this->assertInstanceOf('Innmind\\Neo4j\\ONM\\Mapping\\Reader\\YamlReader', Readers::getReader('yaml'));
    }
}
