<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Identity;

use Innmind\Neo4j\ONM\{
    Identity\Uuid,
    Identity,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class UuidTest extends TestCase
{
    public function testInterface()
    {
        $uuid = new Uuid($string = '11111111-1111-1111-1111-111111111111');

        $this->assertInstanceOf(Identity::class, $uuid);
        $this->assertSame($string, $uuid->value());
        $this->assertSame($string, $uuid->toString());
    }

    public function testThrowWhenGlobalFormatNotRespected()
    {
        $this->expectException(DomainException::class);

        new Uuid('foo');
    }

    public function testThrowWhenInvalidCharacters()
    {
        $this->expectException(DomainException::class);

        new Uuid('11111111-1111-1111-1111-11111111111z');
    }
}
