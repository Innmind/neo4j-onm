<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Metadata\Identity;

class IdentityTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $i = new Identity('uuid', 'UUID');

        $this->assertSame('uuid', (string) $i);
        $this->assertSame('uuid', $i->property());
        $this->assertSame('UUID', $i->type());
    }
}
