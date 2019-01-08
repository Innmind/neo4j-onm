<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\Metadata\Identity;
use PHPUnit\Framework\TestCase;

class IdentityTest extends TestCase
{
    public function testInterface()
    {
        $identity = new Identity('uuid', 'UUID');

        $this->assertSame('uuid', (string) $identity);
        $this->assertSame('uuid', $identity->property());
        $this->assertSame('UUID', $identity->type());
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\DomainException
     */
    public function testThrowWhenEmptyProperty()
    {
        new Identity('', 'UUID');
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\DomainException
     */
    public function testThrowWhenEmptyType()
    {
        new Identity('uuid', '');
    }
}
