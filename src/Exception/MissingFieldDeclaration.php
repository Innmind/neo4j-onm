<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Exception;

final class MissingFieldDeclaration extends InvalidArgumentException
{
    public function __construct(string $field)
    {
        parent::__construct(sprintf(
            'Missing config key "%s" in type declaration',
            $field
        ));
    }
}
