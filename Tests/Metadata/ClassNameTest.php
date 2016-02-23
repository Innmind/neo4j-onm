<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\ClassName;

class ClassNameTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $c = new ClassName('Class\Name\Space');

        $this->assertSame('Class\Name\Space', (string) $c);
    }
}
