<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\Metadata\EntityInterface;
use Innmind\Neo4j\DBAL\ResultInterface;
use Innmind\Immutable\SetInterface;

interface EntityTranslatorInterface
{
    /**
     * Translate the wished variable from the result
     *
     * @param string $variable
     * @param EntityInterface $meta
     * @param ResultInterface $result
     *
     * @return SetInterface<MapInterface<string, mixed>>
     */
    public function translate(
        string $variable,
        EntityInterface $meta,
        ResultInterface $result
    ): SetInterface;
}
