<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    IdentityMatch,
    Metadata\Entity
};
use Innmind\Neo4j\DBAL\Query\Query;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class IdentityMatchTest extends TestCase
{
    public function testInterface()
    {
        $i = new IdentityMatch(
            $q = new Query,
            $v = new Map('string', Entity::class)
        );

        $this->assertSame($q, $i->query());
        $this->assertSame($v, $i->variables());
    }

    /**
     * @expectedException Innmind\Neo4j\ONM\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidVariableMap()
    {
        new IdentityMatch(
            new Query,
            new Map('int', 'int')
        );
    }
}
