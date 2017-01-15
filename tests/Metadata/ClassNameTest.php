<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Metadata\ClassName;

class ClassNameTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $c = new ClassName('Class\Name\Space');

        $this->assertSame('Class\Name\Space', (string) $c);
    }
}
