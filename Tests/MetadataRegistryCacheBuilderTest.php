<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\MetadataRegistry;
use Innmind\Neo4j\ONM\MetadataRegistryCacheBuilder;
use Innmind\Neo4j\ONM\Mapping\NodeMetadata;
use Innmind\Neo4j\ONM\Mapping\RelationshipMetadata;
use Innmind\Neo4j\ONM\Mapping\Property;
use Innmind\Neo4j\ONM\Mapping\Id;

class MetadataRegistryCacheBuilderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetRegistryCode()
    {
        $registry = new MetadataRegistry;
        $node = new NodeMetadata;
        $node
            ->setClass('Resource')
            ->setRepositoryClass('stdClassRepo')
            ->setAlias('N')
            ->setId(
                (new Id)
                    ->setProperty('id')
                    ->setType('int')
                    ->setStrategy('AUTO')
            )
            ->addproperty(
                (new Property)
                    ->setName('foo')
                    ->setType('int')
                    ->setNullable(true)
                    ->addOption('foo', ['bar' => 'baz'])
            );
        $node->addLabel('Foo');
        $registry->addMetadata($node);
        $relationship = new RelationshipMetadata;
        $relationship
            ->setClass('Referer')
            ->setId(
                (new Id)
                    ->setProperty('id')
                    ->setType('int')
                    ->setStrategy('AUTO')
            );
        $relationship->setType('FOO');
        $registry->addMetadata($relationship);
        $expected = <<<EOF
<?php

\$registry = new Innmind\Neo4j\ONM\MetadataRegistry;
\$meta = new Innmind\Neo4j\ONM\Mapping\NodeMetadata;
\$meta
    ->setClass('Resource')
    ->setRepositoryClass('stdClassRepo')
    ->setId(
        (new Innmind\Neo4j\ONM\Mapping\Id)
            ->setProperty('id')
            ->setType('int')
            ->setStrategy('AUTO')
    );
\$meta->setAlias('N');
\$meta->addLabel('Foo');
\$meta->addProperty(
    (new Innmind\Neo4j\ONM\Mapping\Property)
        ->setName('foo')
        ->setType('int')
        ->setNullable(true)
        ->addOption('foo', array (
  'bar' => 'baz',
))
);
\$registry->addMetadata(\$meta);
\$meta = new Innmind\Neo4j\ONM\Mapping\RelationshipMetadata;
\$meta
    ->setClass('Referer')
    ->setRepositoryClass('Innmind\\\Neo4j\\\ONM\\\RelationshipRepository')
    ->setId(
        (new Innmind\Neo4j\ONM\Mapping\Id)
            ->setProperty('id')
            ->setType('int')
            ->setStrategy('AUTO')
    );
\$meta->setType('FOO');
\$registry->addMetadata(\$meta);
return \$registry;
EOF;

        $this->assertEquals(
            $expected,
            MetadataRegistryCacheBuilder::getCode($registry)
        );
    }
}
