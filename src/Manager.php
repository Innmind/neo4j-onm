<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Identity\Generators;
use Innmind\Neo4j\DBAL\Connection;

interface Manager
{
    public function connection(): Connection;
    public function repository(string $class): Repository;

    /**
     * Persist all the entities' modifications
     */
    public function flush(): void;
    public function identities(): Generators;
}
