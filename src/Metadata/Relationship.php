<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Metadata;

final class Relationship extends Entity implements EntityInterface
{
    private $type;
    private $startNode;
    private $endNode;

    public function __construct(
        ClassName $class,
        Identity $id,
        Repository $repository,
        Factory $factory,
        Alias $alias,
        RelationshipType $type,
        RelationshipEdge $startNode,
        RelationshipEdge $endNode
    ) {
        parent::__construct($class, $id, $repository, $factory, $alias);

        $this->type = $type;
        $this->startNode = $startNode;
        $this->endNode = $endNode;
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
