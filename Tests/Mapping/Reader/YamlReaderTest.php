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
        $this->assertEquals(
            'string',
            $props['uuid']->getType()
        );
        $this->assertTrue(isset($props['referers']));

        $this->assertEquals(
            'RefererRepository',
            $rel->getRepositoryClass()
        );
        $this->assertEquals('RF', $rel->getAlias());
        $this->assertEquals('uuid', $rel->getId()->getProperty());
        $this->assertEquals('REFER', $rel->getType());
        $this->assertTrue($rel->hasStartNode());
        $this->assertTrue($rel->hasEndNode());
        $this->assertSame('referer', $rel->getStartNode());
        $this->assertSame('resource', $rel->getEndNode());

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

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage The relationship "Referer" can't have a relationship property on "referer"
     */
    public function testThrowWhenSettingRelationshipOnRelationship()
    {
        $r = new YamlReader;

        $metas = $r->load('fixtures/relationship-error.yml');
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage The node "Resource" can't have the property "referer" type set to "startNode"
     */
    public function testThrowWhenSettingStartNodeOnNode()
    {
        $r = new YamlReader;

        $metas = $r->load('fixtures/node-startNode-error.yml');
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage The node "Resource" can't have the property "referer" type set to "endNode"
     */
    public function testThrowWhenSettingEndNodeOnNode()
    {
        $r = new YamlReader;

        $metas = $r->load('fixtures/node-endNode-error.yml');
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Missing option "relationship" for the property "referers" on "Resource"
     */
    public function testThrowWhenMissingOptionRelationship()
    {
        $r = new YamlReader;

        $metas = $r->load('fixtures/option-relationship-error.yml');
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Missing option "node" for the property "referer" on "Referer"
     */
    public function testThrowWhenMissingOptionNode()
    {
        $r = new YamlReader;

        $metas = $r->load('fixtures/option-node-error.yml');
    }
}
