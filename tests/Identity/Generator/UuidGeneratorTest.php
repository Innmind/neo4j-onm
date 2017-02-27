<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Identity\Generator;

use Innmind\Neo4j\ONM\{
    IdentityInterface,
    Identity\Uuid,
    Identity\GeneratorInterface,
    Identity\Generator\UuidGenerator
};
use PHPUnit\Framework\TestCase;

class UuidGeneratorTest extends TestCase
{
    public function testInterface()
    {
        $g = new UuidGenerator;

        $this->assertInstanceOf(GeneratorInterface::class, $g);
        $u = $g->new();
        $this->assertInstanceOf(Uuid::class, $u);
        $this->assertFalse($g->knows('11111111-1111-1111-1111-111111111111'));
        $this->assertTrue($g->knows($u->value()));
        $this->assertSame($u, $g->get($u->value()));
    }

    public function testAdd()
    {
        $g = new UuidGenerator;

        $u = new Uuid($s = '11111111-1111-1111-1111-111111111111');
        $this->assertFalse($g->knows($s));
        $this->assertSame($g, $g->add($u));
        $this->assertTrue($g->knows($s));
    }

    public function testFor()
    {
        $g = new UuidGenerator;
        $s = '11111111-1111-1111-1111-111111111111';

        $this->assertFalse($g->knows($s));
        $this->assertInstanceOf(Uuid::class, $u = $g->for($s));
        $this->assertSame($s, $u->value());
        $this->assertTrue($g->knows($s));
        $this->assertSame($u, $g->for($s));
    }

    public function testGenerateWishedClass()
    {
        $uuid = new class('foo') implements IdentityInterface
        {
            private $value;

            public function __construct(string $value)
            {
                $this->value = $value;
            }

            public function value()
            {
                return $this->value;
            }

            public function __toString(): string
            {
                return $this->value;
            }
        };
        $g = new UuidGenerator(get_class($uuid));

        $uuid2 = $g->new();
        $this->assertInstanceOf(get_class($uuid), $uuid2);
        $this->assertRegExp(Uuid::PATTERN, $uuid2->value());
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenEmptyType()
    {
        new UuidGenerator('');
    }
}
