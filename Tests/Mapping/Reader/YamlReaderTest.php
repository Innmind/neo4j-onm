<?php

namespace Innmind\Neo4j\ONM\Tests\Mapping\Reader;

use Innmind\Neo4j\ONM\Mapping\Reader\YamlReader;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\RelationshipMetadata;

class YamlReaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoadFile()
    {
        $r = new YamlReader;

        $metas = $r->load('fixtures/metadata.yml');

        $this->assertEquals(2, count($metas));

        $node = $metas[0];
        $rel = $metas[1];

        $this->assertInstanceof(
            'Innmind\\Neo4j\\ONM\\Mapping\\NodeMetadata',
            $node
        );
        $this->assertInstanceof(
            'Innmind\\Neo4j\\ONM\\Mapping\\RelationshipMetadata',
            $rel
        );
        $this->assertEquals('Resource', $node->getClass());
        $this->assertEquals(
            'ResourceRepository',
            $node->getRepositoryClass()
        );
        $this->assertEquals('R', $node->getAlias());
        $this->assertEquals('uuid', $node->getId()->getProperty());
        $this->assertEquals(['Resource'], $node->getLabels());

        $props = $node->getProperties();

        $this->assertEquals(
            'url',
            $props['url']->getName()
        );
        $this->assertEquals(
            'string',
            $props['url']->getType()
        );
        $this->assertEquals(
            255,
            $props['url']->getOption('length')
        );
        $this->assertTrue(isset($props['referers']));

        $this->assertEquals(
            'RefererRepository',
            $rel->getRepositoryClass()
        );
        $this->assertEquals('RF', $rel->getAlias());
        $this->assertEquals('uuid', $rel->getId()->getProperty());
        $this->assertEquals('REFER', $rel->getType());

        $props = $rel->getProperties();

        $this->assertEquals(
            'startNode',
            $props['referer']->getType()
        );
        $this->assertEquals(
            'Resource',
            $props['referer']->getOption('node')
        );
    }
}
