<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Event;

use Innmind\Neo4j\ONM\IdentityInterface;
use Innmind\Immutable\CollectionInterface;

class UpdateEvent extends AbstractEvent
{
    private $changeset;

    public function __construct(
        IdentityInterface $identity,
        $entity,
        CollectionInterface $changeset
    ) {
        parent::__construct($identity, $entity);

        $this->changeset = $changeset;
    }

    public function changeset(): CollectionInterface
    {
        return $this->changeset;
    }
}
