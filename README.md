# Neo4j-ONM

| `master` | `develop` |
|----------|-----------|
| [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/?branch=master) | [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/?branch=develop) |
| [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/?branch=master) | [![Code Coverage](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/?branch=develop) |
| [![Build Status](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/badges/build.png?b=master)](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/build-status/master) | [![Build Status](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/Innmind/neo4j-onm/build-status/develop) |

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/91ce7760-85e1-4d4a-9ea5-6b2cc47c1d15/big.png)](https://insight.sensiolabs.com/projects/91ce7760-85e1-4d4a-9ea5-6b2cc47c1d15)

This an _ORM_ for the [Neo4j](http://neo4j.com/) graph database, with an emphasis on Domain Driven Design (DDD). It will allow you to easily build `Entities`, `Repositories` and query them via `Specification`s. Another important aspect is that each block of this library is fully replaceable.

## Installation

Run the following command to add this library to your project via composer:

```sh
composer require innmind/neo4j-onm
```

## Documentation

### Structure

This library aims at persisting 2 types of objects: `Aggregate` and `Relationship` (both are entities).  The first one represent a node in Neo4j, and can have a set of sub nodes linked to it. Only the main node contains an `Identity` and the sub nodes can't be queried outside their aggregates. The `Relationship` represent a relationship in Neo4j. It always contains an `Identity` and the 2 identities representing the aggregates at the start and end of the relationship.

As described by the DDD, entities objects are not directly linked to each other; instead they contains identities of the entities they point to. However, when those entities are persisted in the graph, the relationships are correctly as you would expect (allowing any other script to query your graph normally).
For example, if you would like 2 `Aggregate`s to be connected to each other you would create a new `Relationship` containing the identities of both aggregates; hence you would have to persist 3 objects.

Each entity is fully managed by its own `Repository`, meaning it's used to `add`, `remove`, `get` and query entities.

**Note**: for performance issues, when you `add` an entity to its repository it's not directly inserted in the graph.

To access an entity repository, you'll use a `Manager` which only contains 4 methods: `connection`, `repository`, `flush` and `new`. The first one gives you access to the DBAL [`Connection`](https://github.com/Innmind/neo4j-dbal/blob/master/ConnectionInterface.php) so you can open/commit transactions. The method `repository` takes the entity class in order to return the associated repository. `flush` will persist in the graph all of your modifications from your repositories. Finally, `new` allows you to generate a new identity of the specified type

When you `flush` the sequence of how the modifications are persisted is as follow:

* insert new aggregates
* insert new relationships (in the same query as aggregates)
* update all entities (without any particular order)
* remove relationships
* remove aggregates (in the same query as aggregates)

### Configuration

You're first job is to write the mapping of your entities. Here's a complete example of what you can specify:

```yaml
Image:
    type: aggregate
    alias: I # optional
    repository: ImageRepository #optional
    factory: ImageFactory # optional
    labels: [Image]
    identity:
        property: uuid
        type: Innmind\Neo4j\ONM\Identity\Uuid
    properties:
        url:
            type: string
    children: # optional
        rel:
            class: DescriptionOf
            type: DESCRIPTION_OF # direction flows from value object to aggregate root
            properties:
                created:
                    type: date
            child:
                property: description
                class: Description
                labels: [Description]
                properties:
                    content:
                        type: string

SomeRelationship:
    type: relationship
    alias: SR # optional
    repository: SRRepository # optional
    factory: SRFactory # optional
    rel_type: SOME_RELATIONSHIP
    identity:
        property: uuid
        type: Innmind\Neo4j\ONM\Identity\Uuid
    startNode:
        property: startProperty # must be an IdentifierInterface
        type: Innmind\Neo4j\ONM\Identity\Uuid
        target: uuid # the node property where to look for the identity
    endNode:
        property: endProperty
        type: Innmind\Neo4j\ONM\Identity\Uuid
        target: uuid
    properties:
        created:
            type: date
```

Both `aggregate`s and `relationship`s have:

* a class, obviously
* an `identity`, the property on which the `Identity` object is specified
* a repository, which is optional as by default it uses [`Repository`](Repository.php)
* a factory, optional as well. It's job is to translate a raw collection of data into an instance of your entity
* an alias, optional. It's useful in the case you don't want to type the full FQCN of your entity
* properties. It's the list of properties inside of your objects you want persisted in the graph

For aggregates you'll need to specify the `labels` which are the labels put on the node inside the graph. And for relationships, `rel_type` is the type used to create the graph relationship.

**Note**: for conciseness `yaml` is used here but you see that it's not required to use this library.

### Usage

The first step is to create a manager:

```php
use Innmind\Neo4j\ONM\ManagerFactory;
use Innmind\Neo4j\DBAL\ConnectionFactory;
use Symfony\Component\Yaml\Yaml;

$manager = ManagerFactory::for([Yaml::parse(file_get_contents('path/to/entity_mapping.yml'))])
    ->withConnection(
        ConnectionFactory::on('localhost')
            ->for('neo4j', 'pwd')
            ->build()
    )
    ->build();
```

Now that you a working manager, let's handle our entities:

```php
$images = $manager->repository(Image::class);
$rels = $manager->repository(SomeRelationship::class);
$image1 = new Image($manager->new(Uuid::class));
$image2 = new Image($manager->new(Uuid::class));
$rel = new SomeRelationship(
    $manager->new(Uuid::class),
    $image1->uuid(),
    $image2->uuid()
);

$rels->add($rel);
$images
    ->add($image1)
    ->add($image2);
$manager->flush();
```

The example above will create the given path in your graph: `(:Image {uuid: "some value"})-[:SOME_RELATIONSHIP {uuid: "some value"}]->(:Image {uuid: "some value"})`.

So, even if in your objects there's no direct link between your aggregates and the relationship, it creates a concrete path in your graph. Consequently, if you try the code below, it will throw an exception saying you can't delete your aggregate as it's part of a relationship preventing you creating inconsistencies.

```php
$images->remove($image1);
$manager->flush(); //throw an exception at the database level
```

However the following code would work if you really need to delete the aggregate.

```php
$images->remove($image1);
$rels->remove($rel);
$manager->flush();
```

**Note**: as said earlier, the order of the `remove` calls doesn't matter as the library will always remove relationships (only the ones you asked for removal of course) before the aggregates to prevent unexpected exceptions from the database.

### Querying

Now that you know how to add/remove, let's learn how query our entities back from the graph.

```php
$image = images->get(new Uuid($_GET['wished_image_id']));
```

**Note**: the usage of `$_GET` here is only to be framework agnostic, but event if you'd use it would be pretty safe as `Uuid` validates the data (as you can see [here](Identity/Uuid.php#L20)).

But accessing entities through their identifiers is not enough, that's why a repository as a method called `matching` which allows only a single parameter that has to be a [specification](https://github.com/Innmind/Specification).

A specification is a good fit for querying objects as this pattern aims at verifying if an object match a certain criteria, which is what we want to accomplish when retrieving our entities. The advantage with this is that it removes duplication in your codebase; no more specific query language to query your objects.

Example:

```php
$entities = $images->matching(
    $spec = (new ImageOfDomain('example.org'))
        ->or(new ImageOfDomain('antoher.net'))
        ->and((new ImageOfDomain('exclude.net'))->not())
);
```

Here `ImageOfDomain` would use the image `url` to check if it's one of the wished one. The library can translate any tree of specification into a valid cypher query. And because `ImageOfDomain` should implement a method like `isSatisfiedBy` you can reuse `$spec` to validate any `Image` elsewhere in your code.

### Overriding defaults

The library is decoupled enough so most of its building blocks an be easily replaced, allowing you to improve it if you feel limited in your use case.

#### Types

By default there's only 7 types you can use for your entities' properties:

* `array`
* `bool` (or `boolean`)
* `date` (or `datetime`)
* `float`
* `int` (or `integer`)
* `set` (similar as `array` except it uses the immutable [`Set`](https://github.com/Innmind/Immutable#set))
* `string`

To add your own type you need to create a class implementing [`TypeInterface.php`](TypeInterface.php) and on the manager factory call `withType` with your class name as parameter.

Example:

```php
use Innmind\Neo4j\ONM\TypeInterface;

class MyType implements TypeInterface
{
    // your implementation ...
}

$manager = ManagerFactory::for([/* your mapping */])
    ->withConnection($conn)
    ->withType(MyType::class)
    ->build();
```

#### Configuration

By default the mapping of your entities is validated against the class [`Configuration`](Configuration.php). If you think it lacks validation or you want to add some sugar during the normalization you can create your own class implementing [`ConfigurationInterface`](https://github.com/symfony/config/blob/3.0/Definition/ConfigurationInterface.php).

Once done, you need to specify your config object when building your manager:

```php
ManagerFactory::for([/* mapping */])
    ->validatedBy(new MyConfigurationClass)
    ->withConnection($conn)
    ->build();
```

#### Metadata factories

To translate the mapping arrays of your entities into objects, the library use a set of [`MetadataFactoryInterface`](MetadataFactoryInterface.php). More precisely one for aggregates and one for relationships.

If you're not satisfied with those defaults, you can create your own factories to replace them or simply new ones to create new kinds of metadata objects. To do so you need to create a class implementing the interface mentioned above and register it when building the manager.

```php
use Innmind\Neo4j\ONM\{
    MetadataFactoryInterface,
    Metadata\Aggregate
};
use Innmind\Immutable\Map;

class MyAggregateFactory implements MetadataFactoryInterface
{
    // your implementation
}

ManagerFactory::for([/* mapping */])
    ->withMetadataFactories(
        (new Map('string', MetadataFactoryInterface::class))
            ->put(Aggregate::class, new MyAggregateFactory)
    );
```

Here you specify to use your own factory to be used to build `Aggregate`s metadatas. If you decide to create a new type of [`EntityInterface`](Metadata/EntityInterface.php) you would need to replace `Aggregate::class` by `MyEntityMetadataType::class` (in such case you would also need to override the default configuration class, as explained in the previous section).

#### Entity Translators

When querying the graph to load your entities, there's a step where the result returned from connection is translated into a collection of raw structured data that look like the structure of your entities. This data is afterward used by factories to create your entities.

In case you've built a new kind of entity metadata (see section above), you'll need to create a new translator.

```php
use Innmind\Neo4jONM\Translation\EntityTranslatorInterface;
use Innmind\Immutable\Map;

class MyTranslator implements EntityTranslatorInterface
{
    // your implementation ...
}

ManagerFactory::for([/* mapping */])
    ->withEntityTranslators(
        (new Map('string', EntityTranslatorInterface::class))
            ->put(MyEntityMetadata::class, new MyTranslator)
    );
```

#### Entity factories

By default the library use 2 factories to translate raw data into your entities and both relies on the library [`Reflection`](https://github.com/Innmind/Reflection) to build objects.

In case your entity is too complex to be built via the default tools, you can build your own entity factory to resolve your limitation.

```php
use Innmind\Neo4j\ONM\EntityFactoryInterface;

class MyEntityFactory implements EntityFactoryInterface
{
    // your implementation
}

ManagerFactory::for([/* mapping */])
    ->withEntityFactory(new MyEntityFactory);
```

**Note**: for your factory to be really used, you'll need in the mapping of your entity to specify the class of your factory.

#### Identity generators

By default this library only use UUIDs as identity objects. But you can easily add your own kind of identity object.

You need to create the identity class implementing [`IdentityInterface`](IdentityInterface.php) and the corresponding generator implementing [`GeneratorInterface`](Identity/GeneratorInterface.php).

```php
use Innmind\Neo4j\ONM\{
    IdentityInterface,
    Identity\GeneratorInterface
};

class MyIdentity implements IdentityInterface
{
    // your implementation ...
}

class MyIdentityGenerator implements GeneratorInterface
{
    // your implementation
}

ManagerFactory::for([/* mapping */])
    ->withGenerator(MyIdentity::class, new MyIdentityGenerator);
```
