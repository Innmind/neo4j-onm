<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    IdentityMatch,
    Metadata\Entity,
};
use Innmind\Neo4j\DBAL\Query\Query;
use Innmind\Immutable\Map;
use PHPUnit\Framework\TestCase;

class IdentityMatchTest extends TestCase
{
    public function testInterface()
    {
        $identity = new IdentityMatch(
            $query = new Query,
            $variables = Map::of('string', Entity::class)
        );

        $this->assertSame($query, $identity->query());
        $this->assertSame($variables, $identity->variables());
    }

    public function testThrowWhenInvalidVariableMap()
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('Argument 2 must be of type Map<string, Innmind\Neo4j\ONM\Metadata\Entity>');

        new IdentityMatch(
            new Query,
            Map::of('int', 'int')
        );
    }
}
