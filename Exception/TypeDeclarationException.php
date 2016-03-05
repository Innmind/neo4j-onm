<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Exception;

class TypeDeclarationException extends \InvalidArgumentException implements ExceptionInterface
{
    public static function missingField(string $field): self
    {
        return new self(sprintf(
            'Missing config key "%s" in type declaration',
            $field
        ));
    }
}
