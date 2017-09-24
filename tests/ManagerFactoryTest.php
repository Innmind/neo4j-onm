<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\{
    ManagerFactory,
    Manager,
    Configuration,
    Translation\EntityTranslator,
    Translation\IdentityMatchTranslator,
    Translation\MatchTranslator,
    Translation\SpecificationTranslator,
    Identity\Uuid,
    Identity\Generator\UuidGenerator,
    EntityFactory,
    MetadataFactory,
    Type,
    Types,
    Persister,
    Repository
};
use Innmind\Neo4j\DBAL\Connection;
use Innmind\EventBus\EventBusInterface;
use Innmind\Immutable\{
    Map,
    SetInterface,
    Set,
    MapInterface
};
use Symfony\Component\Yaml\Yaml;
use PHPUnit\Framework\TestCase;

class ManagerFactoryTest extends TestCase
{
    public function testBasicBuild()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
        $this->assertSame($m, $manager->connection());
    }

    public function testBuildWithGivenConfigInstance()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->validatedBy(new Configuration)
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testBuildWithGivenEntityTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withEntityTranslators(new Map('string', EntityTranslator::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testBuildWithGivenGenerator()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withGenerator(Uuid::class, new UuidGenerator)
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testBuildWithGivenEntityFactory()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withEntityFactory($this->createMock(EntityFactory::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testBuildWithGivenIdentityMatchTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withIdentityMatchTranslators(new Map('string', IdentityMatchTranslator::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testBuildWithGivenMetadataFactories()
    {
        $manager = ManagerFactory::for([])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withMetadataFactories(new Map('string', MetadataFactory::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testBuildWithGivenAdditionalType()
    {
        $mock = new class implements Type
        {
            public static function fromConfig(MapInterface $c, Types $types): Type
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
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withType(get_class($mock))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testBuildWithGivenPersister()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withPersister($this->createMock(Persister::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testBuildWithGivenMatchTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withMatchTranslators(new Map('string', MatchTranslator::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testBuildWithGivenSpecificationTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withSpecificationTranslators(new Map('string', SpecificationTranslator::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testBuildWithGivenRepository()
    {
        $mock = $this->createMock(Repository::class);
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withRepository('Image', $mock)
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(Manager::class, $manager);
        $this->assertSame($mock, $manager->repository('Image'));
    }
}
