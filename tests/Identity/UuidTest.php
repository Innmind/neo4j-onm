<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Identity;

use Innmind\Neo4j\ONM\{
    Identity\Uuid,
    IdentityInterface
};
use PHPUnit\Framework\TestCase;

class UuidTest extends TestCase
{
    public function testInterface()
    {
        $u = new Uuid($s = '11111111-1111-1111-1111-111111111111');

        $this->assertInstanceOf(IdentityInterface::class, $u);
        $this->assertSame($s, $u->value());
        $this->assertSame($s, (string) $u);
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenGlobalFormatNotRespected()
    {
        new Uuid('foo');
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidCharacters()
    {
        new Uuid('11111111-1111-1111-1111-11111111111z');
    }
}
