<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    Translation\ResultTranslator,
    Identity\Generators,
    EntityFactory\Resolver,
    Metadata\EntityInterface,
    Entity\Container
};
use Innmind\Neo4j\DBAL\ResultInterface;
use Innmind\Immutable\{
    Map,
    Set,
    SetInterface,
    MapInterface,
    CollectionInterface
};

class EntityFactory
{
    private $translator;
    private $generators;
    private $resolver;
    private $entities;

    public function __construct(
        ResultTranslator $translator,
        Generators $generators,
        Resolver $resolver,
        Container $entities
    ) {
        $this->translator = $translator;
        $this->generators = $generators;
        $this->resolver = $resolver;
        $this->entities = $entities;
    }

    /**
     * Translate the dbal result into a ste of entities
     *
     * @param ResultInterface $result
     * @param MapInterface<string, EntityInterface> $variables
     *
     * @return SetInterface<object>
     */
    public function make(
        ResultInterface $result,
        MapInterface $variables
    ): SetInterface {
        $structuredData = $this->translator->translate($result, $variables);
        $entities = new Set('object');

        $variables->foreach(function(
            string $variable,
            EntityInterface $meta
        ) use (
            &$entities,
            $structuredData
        ) {
            $data = $structuredData->get($variable);

            if ($data->hasKey(0)) { // means collection
                $data->each(function(
                    int $index,
                    CollectionInterface $data
                ) use (
                    &$entities,
                    $meta
                ) {
                    $entities = $entities->add(
                        $this->makeEntity(
                            $meta,
                            $data
                        )
                    );
                });
            } else {
                $entities = $entities->add(
                    $this->makeEntity(
                        $meta,
                        $data
                    )
                );
            }
        });

        return $entities;
    }

    private function makeEntity(EntityInterface $meta, CollectionInterface $data)
    {
        $identity = $this
            ->generators
            ->get($meta->identity()->type())
            ->for(
                $data->get($meta->identity()->property())
            );

        if ($this->entities->contains($identity)) {
            return $this->entities->get($identity);
        }

        $entity = $this
            ->resolver
            ->get($meta)
            ->make($identity, $meta, $data);
        $this->entities = $this->entities->push(
            $identity,
            $entity,
            Container::STATE_MANAGED
        );

        return $entity;
    }
}
