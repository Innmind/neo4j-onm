<?php
declare(strict_types = 1);

use Innmind\Neo4j\ONM\{
    Metadata\Aggregate,
    Metadata\Relationship,
    Metadata\ClassName,
    Metadata\Identity,
    Metadata\RelationshipType,
    Metadata\RelationshipEdge,
    Metadata\Child,
    Metadata\ChildRelationship,
    Type,
    Type\StringType,
    Type\DateType,
};
use Innmind\Immutable\{
    Map,
    Set,
};

return [
    Aggregate::of(
        new ClassName('Image'),
        new Identity('uuid', 'UUID'),
        Set::of('string', 'Image'),
        Map::of('string', Type::class)
            ('url', new StringType),
        Set::of(
            Child::class,
            Child::of(
                new ClassName('Description'),
                Set::of('string', 'Description'),
                ChildRelationship::of(
                    new ClassName('DescriptionOf'),
                    new RelationshipType('DESCRIPTION_OF'),
                    'rel',
                    'description',
                    Map::of('string', Type::class)
                        ('created', new DateType)
                ),
                Map::of('string', Type::class)
                    ('content', new StringType)
            )
        )
    ),
    Relationship::of(
        new ClassName('SomeRelationship'),
        new Identity('uuid', 'UUID'),
        new RelationshipType('SOME_RELATIONSHIP'),
        new RelationshipEdge('startProperty', 'UUID', 'uuid'),
        new RelationshipEdge('endProperty', 'UUID', 'uuid'),
        Map::of('string', Type::class)
            ('created', new DateType)
    )
];
