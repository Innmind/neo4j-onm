<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Translation;

use Innmind\Neo4j\ONM\{
    Metadata\EntityInterface,
    IdentityInterface,
    IdentityMatch
};

interface IdentityMatchTranslatorInterface
{
    /**
     * Translate an identity into a query to match the expected element
     *
     * @param EntityInterface $meta
     * @param IdentityInterface $identity
     *
     * @return IdentityMatch
     */
    public function translate(
        EntityInterface $meta,
        IdentityInterface $identity
    ): IdentityMatch;
}
