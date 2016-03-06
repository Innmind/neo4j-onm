<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Identity\Generator;

use Innmind\Neo4j\ONM\{
    IdentityInterface,
    Identity\Uuid,
    Identity\GeneratorInterface,
    Identity\Generator\UuidGenerator
};

class UuidGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $g = new UuidGenerator;

        $this->assertInstanceof(GeneratorInterface::class, $g);
        $u = $g->new();
        $this->assertInstanceof(Uuid::class, $u);
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
}
