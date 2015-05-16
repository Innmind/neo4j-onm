<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\MetadataRegistry;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;

class MetadataRegistryTest extends \PHPUnit_Framework_TestCase
{
    public function testAddMetadata()
    {
        $r = new MetadataRegistry;
        $m = new NodeMetadata;
        $m->setClass('stdClass');

        $this->assertSame(
            $r,
            $r->addMetadata($m)
        );
        $this->assertSame(
            $m,
            $r->getMetadata('stdClass')
        );
    }

    public function testFetMetadatas()
    {
        $r = new MetadataRegistry;
        $m = new NodeMetadata;
        $m->setClass('stdClass');
        $r->addMetadata($m);

        $this->assertSame(
            ['stdClass' => $m],
            $r->getMetadatas()
        );
    }
}
