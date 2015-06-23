<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Mapping\Metadata;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\RelationshipMetadata;
use Innmind\Neo4j\ONM\Mapping\Property;

class MetadataRegistryCacheBuilder
{
    /**
     * Build the php code to be dumped in cache file
     *
     * @param MetadataRegistry $registry
     *
     * @return string
     */
    public static function getCode(MetadataRegistry $registry)
    {
        $registryClass = get_class($registry);
        $code = <<<EOF
<?php

\$registry = new $registryClass;
EOF;

        foreach ($registry->getMetadatas() as $meta) {
            $code .= self::getMetadataCode($meta);
        }

        $code .= <<<EOF

return \$registry;
EOF;

        return $code;
    }

    /**
     * Generate code for build a metadata object
     *
     * @param Metadata $meta
     *
     * @return string
     */
    protected static function getMetadataCode(Metadata $meta)
    {
        $metaClass = get_class($meta);
        $entityClass = addslashes($meta->getClass());
        $repository = addslashes($meta->getRepositoryClass());
        $id = $meta->getId();
        $idClass = get_class($id);
        $property = addslashes($id->getProperty());
        $type = addslashes($id->getType());
        $strategy = addslashes($id->getStrategy());

        $code = <<<EOF

\$meta = new $metaClass;
\$meta
    ->setClass('$entityClass')
    ->setRepositoryClass('$repository')
    ->setId(
        (new $idClass)
            ->setProperty('$property')
            ->setType('$type')
            ->setStrategy('$strategy')
    );
EOF;

        if ($meta->hasAlias()) {
            $alias = addslashes($meta->getAlias());
            $code .= <<<EOF

\$meta->setAlias('$alias');
EOF;
        }

        if ($meta instanceof NodeMetadata) {
            $labels = $meta->getLabels();

            foreach ($labels as $label) {
                $label = addslashes($label);

                $code .= <<<EOF

\$meta->addLabel('$label');
EOF;
            }
        }

        if ($meta instanceof RelationshipMetadata) {
            $type = addslashes($meta->getType());

            $code .= <<<EOF

\$meta->setType('$type');
EOF;

            if ($meta->hasStartNode()) {
                $startNode = $meta->getStartNode();

                $code .= <<<EOF

\$meta->setStartNode('$startNode');
EOF;
            }

            if ($meta->hasEndNode()) {
                $endNode = $meta->getEndNode();

                $code .= <<<EOF

\$meta->setEndNode('$endNode');
EOF;
            }
        }

        foreach ($meta->getProperties() as $prop) {
            $code .= self::getPropertyCode($prop);
        }

        $code .= <<<EOF

\$registry->addMetadata(\$meta);
EOF;

        return $code;
    }

    /**
     * Generate the code to create a property
     *
     * @param Property $property
     *
     * @return string
     */
    protected static function getPropertyCode(Property $property)
    {
        $propClass = get_class($property);
        $name = addslashes($property->getName());
        $type = addslashes($property->getType());
        $nullable = $property->isNullable() ? 'true' : 'false';

        $code = <<<EOF

\$meta->addProperty(
    (new $propClass)
        ->setName('$name')
        ->setType('$type')
        ->setNullable($nullable)
EOF;

        foreach ($property->getOptions() as $key => $value) {
            $key = addslashes($key);
            $value = var_export($value, true);

            $code .= <<<EOF

        ->addOption('$key', $value)
EOF;
        }

        $code .= <<<EOF

);
EOF;

        return $code;
    }
}
