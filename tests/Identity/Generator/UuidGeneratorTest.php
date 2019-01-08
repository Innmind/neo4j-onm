<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Identity\Generator;

use Innmind\Neo4j\ONM\{
    Identity,
    Identity\Uuid,
    Identity\Generator,
    Identity\Generator\UuidGenerator,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class UuidGeneratorTest extends TestCase
{
    public function testInterface()
    {
        $generator = new UuidGenerator;

        $this->assertInstanceOf(Generator::class, $generator);
        $uuid = $generator->new();
        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertFalse($generator->knows('11111111-1111-1111-1111-111111111111'));
        $this->assertTrue($generator->knows($uuid->value()));
        $this->assertSame($uuid, $generator->get($uuid->value()));
    }

    public function testAdd()
    {
        $generator = new UuidGenerator;

        $uuid = new Uuid($string = '11111111-1111-1111-1111-111111111111');
        $this->assertFalse($generator->knows($string));
        $this->assertSame($generator, $generator->add($uuid));
        $this->assertTrue($generator->knows($string));
    }

    public function testFor()
    {
        $generator = new UuidGenerator;
        $string = '11111111-1111-1111-1111-111111111111';

        $this->assertFalse($generator->knows($string));
        $this->assertInstanceOf(Uuid::class, $uuid = $generator->for($string));
        $this->assertSame($string, $uuid->value());
        $this->assertTrue($generator->knows($string));
        $this->assertSame($uuid, $generator->for($string));
    }

    public function testGenerateWishedClass()
    {
        $uuid = new class('foo') implements Identity
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
        $generator = new UuidGenerator(get_class($uuid));

        $uuid2 = $generator->new();
        $this->assertInstanceOf(get_class($uuid), $uuid2);
        $this->assertRegExp(Uuid::PATTERN, $uuid2->value());
    }

    public function testThrowWhenEmptyType()
    {
        $this->expectException(DomainException::class);

        new UuidGenerator('');
    }
}
