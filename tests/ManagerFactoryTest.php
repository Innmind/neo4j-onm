<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM;

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
    Types,
    PersisterInterface,
    RepositoryInterface
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

        $this->assertInstanceOf(ManagerInterface::class, $manager);
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

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenEntityTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withEntityTranslators(new Map('string', EntityTranslatorInterface::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
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

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenEntityFactory()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withEntityFactory($this->createMock(EntityFactoryInterface::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenIdentityMatchTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withIdentityMatchTranslators(new Map('string', IdentityMatchTranslatorInterface::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenMetadataFactories()
    {
        $manager = ManagerFactory::for([])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withMetadataFactories(new Map('string', MetadataFactoryInterface::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenAdditionalType()
    {
        $mock = new class implements TypeInterface
        {
            public static function fromConfig(MapInterface $c, Types $types): TypeInterface
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

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenPersister()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withPersister($this->createMock(PersisterInterface::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenMatchTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withMatchTranslators(new Map('string', MatchTranslatorInterface::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenSpecificationTranslators()
    {
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withSpecificationTranslators(new Map('string', SpecificationTranslatorInterface::class))
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
    }

    public function testBuildWithGivenRepository()
    {
        $mock = $this->createMock(RepositoryInterface::class);
        $manager = ManagerFactory::for([
            Yaml::parse(file_get_contents('fixtures/mapping.yml'))
        ])
            ->withEventBus($this->createMock(EventBusInterface::class))
            ->withRepository('Image', $mock)
            ->withConnection($m = $this->createMock(Connection::class))
            ->build();

        $this->assertInstanceOf(ManagerInterface::class, $manager);
        $this->assertSame($mock, $manager->repository('Image'));
    }
}
