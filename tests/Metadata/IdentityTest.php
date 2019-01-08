<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    Metadata\Identity,
    Exception\DomainException,
};
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

    public function testThrowWhenEmptyProperty()
    {
        $this->expectException(DomainException::class);

        new Identity('', 'UUID');
    }

    public function testThrowWhenEmptyType()
    {
        $this->expectException(DomainException::class);

        new Identity('uuid', '');
    }
}
