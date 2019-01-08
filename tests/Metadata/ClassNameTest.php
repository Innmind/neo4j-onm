<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Metadata\ClassName;
use PHPUnit\Framework\TestCase;

class ClassNameTest extends TestCase
{
    public function testInterface()
    {
        $className = new ClassName('Class\Name\Space');

        $this->assertSame('Class\Name\Space', (string) $className);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\DomainException
     */
    public function testThrowWhenEmptyClass()
    {
        new ClassName('');
    }
}
