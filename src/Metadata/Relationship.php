<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

use Innmind\Neo4j\ONM\{
    EntityFactory\RelationshipFactory,
    Repository\Repository as ConcreteRepository,
};

final class Relationship extends AbstractEntity implements Entity
{
    private $type;
    private $startNode;
    private $endNode;

    public function __construct(
        ClassName $class,
        Identity $identity,
        RelationshipType $type,
        RelationshipEdge $startNode,
        RelationshipEdge $endNode
    ) {
        parent::__construct(
            $class,
            $identity,
            new Repository(ConcreteRepository::class),
            new Factory(RelationshipFactory::class)
        );

        $this->type = $type;
        $this->startNode = $startNode;
        $this->endNode = $endNode;
    }

    public static function of(
        ClassName $class,
        Identity $identity,
        RelationshipType $type,
        RelationshipEdge $startNode,
        RelationshipEdge $endNode
    ): self {
        return new self(
            $class,
            $identity,
            $type,
            $startNode,
            $endNode
        );
    }

    public function type(): RelationshipType
    {
        return $this->type;
    }

    public function startNode(): RelationshipEdge
    {
        return $this->startNode;
    }

    public function endNode(): RelationshipEdge
    {
        return $this->endNode;
    }
}
