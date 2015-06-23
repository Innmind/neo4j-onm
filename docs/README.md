# Documentation

The following documentation will guide you through the steps to get you up and running. It assumes you already have a basic understanding of how Neo4j works.

## Setup

The first step consist into creating a configuration object and an entity manager. The configuration will load all the data it needs to help the entity manager work correctly with your entities.

```php
use Innmind\Neo4j\ONM\Configuration;
use Innmind\Neo4j\ONM\EntityManagerFactory;

$config = Configuration::create([
    'cache' => '/path/to/cache/folder',
    'reader' => 'yaml',
    'locations' => ['/path/to/entities/config'],
], true);

$manager = EntityManagerFactory::make(
    [
        'username' => 'neo4j',
        'password' => 'neo4j',
    ],
    $config
);
```

The `Configuration::create` method only takes 2 arguments: an array of options and a boolean to indicate if we are in dev mode or not (set by default to `true`). The options array is only composed of 3 keys: `cache`, `reader` and `locations`.

* `cache` is the folder where the library will create some files in order to improve its performance (especially in production mode)
* `reader` is the type of configuration you use to configure your entities (currently there's only on choice which is `yaml`); you can add your [own reader](#create-reader) type
* `locations` is an array of path that will be passed to the reader you chose (paths can be directories or files)

The `EntityManagerFactory::make` takes up to 3 arguments: 

* an array to describe the DBAL connection (see the [documentation](https://github.com/Innmind/neo4j-dbal/tree/master/docs#build-a-connection))
* the `Configuration` object created above
* an [event dispatcher](https://github.com/symfony/EventDispatcher) (if none is provided it will create a new one, retrievable via the method `getDispatcher`)

You now have a working entity manager, but you actually can't use it as you now need to declare your entities.

## Configuration

Your job now is to create a yaml file (or split it in many ones) to describe the entities you want to manage. You can store this file wherever you want, as long as you give the right path in the `locations` key used to create the `Configuration` object (see above).

One looks as follows:

```yaml
Entity\Fully\Qualified\Namespace:
    type: node
    repository: Entity\Repository\Fully\Qualified\Namespace # optional
    alias: MyEntityAlias                                    # optional
    id:
        uuid:
            type: string
            generator: { strategy: UUID }
    labels: [Node, Label]
    properties:
        propertyName:
            type: string
        anotherProperty:
            type: relationship
            relationship: MyRelationshipAlias

Vendor\Project\Entity\Relation:
    type: relationship
    repository: Vendor\Project\Entity\RelationRepository # optional
    alias: MyRelationshipAlias                           # optional
    id:
        uuid:
            type: string
            generator: { strategy: UUID }
    rel_type: MY_REL_TYPE
    properties:
        startNodeProperty:
            type: startNode
            node: MyEntityAlias
        endNodeProperty:
            type: endNode
            node: MyEntityAlias
        anotherProperty:
            type: string
```

As you can see the configuration differs slightly whether you configure a node or a relationship. Let's review what's in common:

* `type`: self explanatory I think, either `node` or `relationship`
* `repository`: class name if you want to create a [custom repository](#custom-repository)
* `alias`: this is a convenient string you can choose to refer to your entity instead of specifying everywhere the fully qualified namespace
* `id`: it defines which property name to use as id (you can replace `uuid` by whatever suits you), the generator strategy will always be `UUID` as it's the only one supported by default (but you can [create your own](#create-generator))
* `properties`: once again, self explanatory; a list of each property you want to map to the database with the associated type

The only differences are related to `labels` and `rel_type`. 

* `labels` list of labels that will be created on a node, i.e.: `[Foo, Bar]` could be represented as `(:Foo:Bar)` in cypher
* `rel_type` is the type of the relationship, i.e.: `FOO` could be represented as `[:FOO]` in cypher

### Property Types

You have access to the following list of types:

* `array`
* `bool` or `boolean` (exactly the same)
* `float`
* `int` or `integer` (exactly the same)
* `json`
* `string`
* `date`

`bool`, `float`, `int` and `string` only ensure the type by doing a simple cast respectively via `(bool)`, `(float)`, `(int)` and `(string)`.

If you use the `array` type, you'll need to add an extra option on your property called `inner_type` which specify the type of the values inside it (which can be any available type, except `array`). Such config may look like this:

```yaml
propertyName:
    type: array
    inner_type: int
```

The `json` type only encodes as a json string the property value. You can specify an option called `associative` (default to `false`) to determine if you want the json data from the database to be converted to an associative array or to an instance of `stdClass`.

The `date` type leverages the `DateTime` class. In case the property value is a string, it will try to build a `DateTime` object from this string and then convert it to the `ISO8601` format to be stored in the database. In any case, you'll always get a `DateTime` object when loading a value from the database.

--- 

There's 3 more *types*: `relationship`, `startNode` and `endNode`. Those are pointers to other entities.

* `relationship` can only be set on an entity representing a *node*. You need to add the extra option `relationship` to specify the relationship that this nodes is connected to
* `startNode` and `endNode` can only be set on an entity representing a *relationship*. You need to add the extra option `node` to specify the nodes the relationship can be set to.

The following (shortened) config:

```yaml
NodeClassA:
    labels: [A]
    properties:
        rel:
            type: relationship
            relationship: Relationship
NodeClassB:
    labels: [B]
    properties:
        rel:
            type: relationship
            relationship: Relationship
Relationship:
    rel_type: REL
    properties:
        a:
            type: startNode
            node: NodeClassA
        b:
            type: endNode
            node: NodeClassB
```

could be represented as the following cypher match: `(:A)-[:REL]->(:B)`.

## Entities

Now that you described your entities, you need to create the corresponding classes. Those are simple PHP classes.

Internally, the library use the [`PropertyAccessor`] to extract and set data from/to your entities. As such, you need to build your classes in order that each property declared in the config (see above) can be accessed via the `PropertyAccessor`.

The only exception is the property declared as *id*, this one only needs to be readable. It's even recommended that you declare it only readable in order to avoid it to be changed in your application. Internally, it uses reflection to set the id when an entity is created.

## Entity manipulation

For this section we'll assume you have the entity `Resource` (as a node) and `Referrer` (as a relationship).

### Persit

The obvious first you'll want to do is to create a node in your database.

```php
$resource = new Resource;
$resource->setName('foo');

$resB = new Resource;
$rel = new Relationship;
$rel->setStart($resource);
$rel->setEnd($resB);

$manager->persist($rel);
$manager->flush();
```

The notion to get here lies in the difference between `persist` and `flush`. The first one tells the manager that the given entity will need to be inserted in the database. The later actually create the entity in the database.

Why this is useful can be answered with a single example. Imagine you need to create a bunch of entities in a loop, at each iteration you call `persist`. With this approach you can call `flush` once after the loop in order to create all entities at once in the database, instead of persisting entities one by one.

Another important point about `persist` is that it's recursive. In the example above it will persist the 3 objects, because both objects `$resource` and `$resB` are attached to the persisted entity `$rel` and that the `start` and `end` properties of `Relationship` are declared with the types `startNode` and `endNode`.

This is really useful in case you create a large graph with your entities, you can persist the whole thing only by calling `persist` on one of its members. 

Obviously, you need cross references to persist a graph at once, otherwise it can't cascade the persit. If we change the above example by persiting `$resource`, it will only persist this object except if in `setStart` it call `setRel` on `$resource` to create the cross reference.

### Update

If you have an entity that is already persisted (or is retrieved later via the manager), you can change the entity properties at will. You only then need to call `flush` on the manager to reflect the changes to the database.

*Note*: we'll see later how to retrieve entities.

### Remove

```php
//$resource = ...;

$manager->remove($resource);
$manager->flush();
```

Same principle as `persist`, `remove` tells the manager that the entity *will* be deleetd at next *flush* (though here there's no recursivity, otherwise we could delete the whole graph).

An important thing when deleting entities is related to relationships. Like in Neo4j, you can't delete a node if it's attached to a relationship. To do this properly you need to do something like this:

```php
$manager->remove($resource->getRel());
$manager->remove($resource);
$manager->flush();
```

This works because the cypher query built use the order in which you call `remove`. 

### Query

The more direct way to retrieve an entity is when you already know the id, in such case you can write the following code:

```php
$resource = $manager->find(Resource::class, 42);
```

Just like this you've retrieved the resource with the id `42`.

*Note*: each entity built by the manager is a proxy, meaning the data will be set only when you'll access a property on the entity. Proxies are needed, otherwise it would load an entire graph when trying to load a single entity.

The `find` method do not restrict you to loading only nodes, you can access directly a relationship.

```php
$rel = $manager->find(Relationship::class, 22);
```

*Note*: when retrieving a same entity twice, the manager will use the id to check if an object already exist, so you can keep the number of objects lower. Also, `find` will look first in its cache to check if the entity with the given id is alreayd loaded (saving a roundtrip to the database).

**Important**: in this section, each time you see the definition `EntityClass::class` it means you could replace the string by the alias defined in your entity configuration

#### Repository

But as you'll rarely know in advance the id of your entities, you need a more advance way to query your database, that's what the repositories are for.

To access a repository:

```php
$repo = $manager->getRepository(Resource::class);
```

From there you have access to the following methods: 

* `find`, is an alias of `EntityManager::find` (see above).
* `findAll`, retrieve all the entities
* `findBy`, use a set of criteria to load entities
* `findOneBy`, same as `findBy` but limit the result to only one entity

Examples:

```php
$entity = $repo->find(42);
$entities = $repo->findAll(); // return an instance of \SplObjectStorage
$entities = $repo->findBy(
    ['property' => 'exact value to match'],
    ['propertyUsedAsSorting', 'ASC|DESC'], // optional
    $limit, // optional
    $skip // optional
); // return an instance of \SplObjectStorage
$entity = $repo->findOneBy(/*same signature as findBy*/); // return an entity or null
```

The default [repository](../Repository.php) also gives a nice shortcut for `findBy` and `findOneBy` when you want to search by one criteria, you can do the following:

```php
$repo->findBy{Property}('value to match' /*same signature*/);
$repo->findOneBy{Property}('value to match' /*same signature*/);
```

Replace `{Property}` by the property you want to search by, i.e.: `findOneByName` to search by the property `name`.

--- 

All the methods described above to search for entities rely on the [`QueryBuilder`](../QueryBuilder.php). This object helps you build cypher queries.

Example:

```php
$qb = $repo->getQueryBuilder(); // it always create a new instance
$qb
    ->match('resource', Resource::class, ['id' => 42])
    ->addExpr(
        $qb
            ->expr()
            ->matchNode()
            ->relatedTo(
                $qb
                    ->expr()
                    ->matchRelationship(
                        'rel', 
                        Relationship::class, 
                        ['id' => 22]
                    )
            )
    )
    ->toReturn('resource, rel')
    ->skip(10)
    ->limit(10);
$result = $manager
    ->getUnitOfWork()
    ->execute($qb->getQuery()); // return an instance of \SplObjectStorage
```

The above example would execute the following cypher: `MATCH (resource:Resource {id: 42}), ()-[rel:Relationship]-() RETURN resource, rel SKIP 10 LIMIT 10;`.

The `$result` variable would contain a mix of nodes and relationships, so be careful when looping on such result.

If the query builder is a bit complex for you, you can write the query by hand like this:

```php
use Innmind\Neo4j\ONM\Query;

$query = new Query('MATCH (var:EntityClassName {id: {where}.id}) RETURN var;');
$query->addVariable('var', 'EntityClassName'); // used to replace the class name by the right labels afterward
$query->addParameters('where', ['id' => '42'], ['id' => 'int']);

$result = $manager
    ->getUnitOfWork()
    ->execute($query);
```

Using this approach you still need to do some work on top of just writing the *cypher*. Note that the cypher you have to write here do not use directly node labels nor relationships types, you write the class name (or the alias) you want to retrieve; the unit of work is able to translate this to a real cypher query by replacing class names by actual node labels/relationship types.

### State

When you call `flush` on the entity manager, it does a dirty check in order to find the entities that need to be updated. The more you handle entities in your script the longer it will takes.

You can improve this step in case you already know the entities that doesn't need to be updated. To do so, you deal with the `detach` method:

```php
$manager->detach($someEntity);
```

This instruct that the manager will no longer try to check if `$someEntity` has changed and needs to be updated.

If you know that all entities of a specific class doesn't need to be updated, you can do so like this:

```php
$manager->clear(Resource::class);
```

And if you omit the class it will detach all entities that the manager knows. 

Of course none of this will affect entities created/retrieved after these operations.

## Transactions

The Neo4j API supports the notion of transactions and so does this library.

To start a new transaction is as simple as this:

```php
$manager->beginTransaction();
```

To commit the changes done in the transaction:

```php
$manager->commit();
```

Or if you want to rollback:

```php
$manager->rollback();
```

If you commit or rollback, it will of course close the current transaction.
 
## Extend the library

The library is built in a way you can extend some of its capabilities, such as the readers, the generators and data types.

### Create reader

The goal of a reader is to extract from some files all the necessary informations in order to build entities' metadata.

One must implement [`ReaderInterface`](../Mapping/ReaderInterface.php). The parameter passed to `load` can be a file path or a directory path (see the *locations* used in `Configuration::create`). The method `getResources` must return all the files you used to load metadata for a location loaded via `load`; this is used in case the location is a directory, the higher level loading the metadata must not know all the details of loading, it just needs the list of the resources loaded so it can pass them to a symfony `ConfigCache`.

`load` must return an array of metadatas, those can be instances either of [`NodeMetadata`](../Mapping/NodeMetadata.php) or [`RelationshipMetadata`](../Mapping/RelationshipMetadata.php).

Example:
```php
class MyReader implements ReaderInterface
{
    protected $resources = [];
    public function load($location)
    {
        $metadatas = [];
        $resources = [];
        if (is_dir($location)) {
            $handle = opendir($location);
            while ($file = readdir($handle)) {
                $metadatas[] = ...;
                $resources[] = realpath($file);
            }
        } else {
            $metadatas[] = new NodeMetadata; // or RelationshipMetadata
            $resources[] = $location
        }
        $this->resources[$location] = $resources;
        return $metadatas;
    }
    public function getResources($location)
    {
        return $this->resources[$location];
    }
}
```

If you load the metadata from a file, like `xml`, you can use the [`FileConfiguration`](../Mapping/Reader/FileConfiguration.php) in order to validate the configuration (see the symfony [doc](http://symfony.com/doc/current/components/config/definition.html#processing-configuration-values)).

Now the last step is to register your reader:

```php
use Innmind\Neo4j\ONM\Mapping\Readers;

Readers::addReader('my_reader', new MyReader);
```

**Note**: This step must be done before creating a [`Configuration`](../Configuration.php) object.

Now you can use your reader:

```php
$config = Configuration::create([
    'cache' => '/tmp',
    'reader' => 'my_reader',
    'locations' => ['/path/to/some/config']
]);
```

### Create generator

A generator is here to generate ids for nodes and relationships. It's called before a new entity is inserted in the database.

One must implement [`GeneratorInterface`](../GeneratorInterface.php).

The method `generate` has access to the `UnitOfWork` and the entity the id will be generated to. Those are here to help you generate the better id possible (for example access the database to check the id you want to give is not already in use, or use the entity class name to increase entropy of the generated id).

**Important**: NEVER inject the id you've generated in the entity, it's done automatically!

The method `getStrategy` must return an unique string (capitalized by convention) that can be used afterward in the entities' configuration.

One could look like this:
```php
class MyGenerator implements GeneratorInterface
{
    public function generate(UnitOfWork $uow, $entity)
    {
        return uniqid(get_class($entity), true);
    }
    public function getStrategy()
    {
        return 'MY_GENERATOR';
    }
}
```

And regiter it (before any config is loaded)

```php
use Innmind\Neo4j\ONM\Generators;

Generators::addGenerator(new MyGenerator);
```

### Create type

A type is a simple utility to convert a value between its PHP representation and the database one (and vice versa).

One must implement [`TypeInterface`](../Mapping/TypeInterface.php).

The both methods of the interface have the same signature, the value to be converted and the [`Property`](../Mapping/Property.php) config the value is intended for. The `Property` object is a convenient way to add some config on a property so you can access this information to better handle the data convertion.

Let's take the example of the date type, by default this type encode the `DateTime` object to the `ISO8601` format. In our example will use the power of property options to let the developer chooser the format he wants to use.

Example of a yaml config:
```yaml
MyNode:
    type: node
    # ... additional config
    properties:
        dateProp
            type: my_date
            format: ATOM  # this is a custom option
```

With such config you can build the following type:

```php
class MyDate implements TypeInterface
{
    public function convertToDatabaseValue($value, Property $property)
    {
        $format = $property->hasOption('format') ? 'ISO8601' : $property->getOption('format');
        //assuming the value is a \DateTime object
        return $value->format('DateTime::'.$format);
    }
    public function convertToPHPValue($value, Property $property)
    {
        // the $value is a string encoded to the format the developer wished
        return new \DateTime($value);
    }
}
```

Now you need to register your type before you load the configuration:

```php
use Innmind\Neo4j\ONM\Mapping\Types;

Types::addType('my_date', MyDate::class);
```

Done!
