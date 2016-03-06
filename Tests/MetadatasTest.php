<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\{
    Metadatas,
    Metadata\Alias,
    Metadata\ClassName,
    Metadata\EntityInterface
};

class MetadatasTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        $m = new Metadatas;

        $this->assertSame(0, $m->all()->size());
        $e = $this->getMock(EntityInterface::class);
        $e
            ->method('alias')
            ->willReturn(new Alias('foo'));
        $e
            ->method('class')
            ->willReturn(new ClassName('bar'));

        $this->assertSame($m, $m->add($e));
        $this->assertSame($e, $m->get('foo'));
        $this->assertSame($e, $m->get('bar'));
        $this->assertSame(1, $m->all()->size());
    }
}
