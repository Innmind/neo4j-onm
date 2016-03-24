<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\{
    ManagerFactory,
    ManagerInterface,
    Configuration,
    Translation\EntityTranslatorInterface,
    Translation\IdentityMatchTranslatorInterface,
    Translation\MatchTranslatorInterface,
    Translation\SpecificationTranslatorInterface,
    Identity\Uuid,
    Identity\Generator\UuidGenerator,
    EntityFactoryInterface,
    MetadataFactoryInterface,
    TypeInterface,
    PersisterInterface,
    RepositoryInterface
};
use Innmind\Neo4j\DBAL\ConnectionInterface;
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set,
    CollectionInterface
};
use Symfony\Component\{
    Yaml\Yaml,
    EventDispatcher\EventDispatcher
};

class ManagerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicBuild()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
        $this->assertSame($m, $manager->connection());
    }

    public function testBuildWithGivenConfigInstance()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->validatedBy(new Configuration)
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenDispatcherInstance()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withDispatcher(new EventDispatcher)
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenEntityTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEntityTranslators(new Map('string', EntityTranslatorInterface::class))
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenGenerator()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withGenerator(Uuid::class, new UuidGenerator)
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenEntityFactory()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEntityFactory($this->getMock(EntityFactoryInterface::class))
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenIdentityMatchTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withIdentityMatchTranslators(new Map('string', IdentityMatchTranslatorInterface::class))
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenMetadataFactories()
    {
        $manager = ManagerFactory::for([])
            ->withMetadataFactories(new Map('string', MetadataFactoryInterface::class))
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenAdditionalType()
    {
        $mock = new class implements TypeInterface
        {
            public static function fromConfig(CollectionInterface $c): TypeInterface
            {
            }

            public function forDatabase($value)
            {
            }

            public function fromDatabase($value)
            {
            }

            public function isNullable(): bool
            {
            }

            public static function identifiers(): SetInterface
            {
                return (new Set('string'))->add('foo');
            }
        };
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withType(get_class($mock))
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenPersister()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withPersister($this->getMock(PersisterInterface::class))
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenMatchTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withMatchTranslators(new Map('string', MatchTranslatorInterface::class))
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenSpecificationTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withSpecificationTranslators(new Map('string', SpecificationTranslatorInterface::class))
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenRepository()
    {
        $mock = $this->getMock(RepositoryInterface::class);
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withRepository('Image', $mock)
            ->withConnection($m = $this->getMock(ConnectionInterface::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
        $this->assertSame($mock, $manager->repository('Image'));
    }
}
