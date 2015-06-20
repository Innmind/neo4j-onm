<?php

namespace Innmind\Neo4j\ONM;

use Innmind\Neo4j\ONM\Mapping\Readers;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Filesystem\Filesystem;
use ProxyManager\Factory\LazyLoadingGhostFactory;
use ProxyManager\Configuration as ProxyConfig;

class Configuration
{
    const METADATA_CACHE_FILE = '/neo4jMetadataRegistry.php';
    const PROXIES_DIRECTORY = '/proxies';

    protected $identityMap;
    protected $metadataRegistry;
    protected $repositoryFactory;
    protected $proxyFactory;

    /**
     * Create a new configuration
     *
     * @param array $config
     * @param bool $devMode
     *
     * @return Configuration
     */
    public static function create(array $config, $devMode = true)
    {
        $resolver = self::buildOptionsResolver();
        $config = $resolver->resolve($config);

        $conf = new self;
        $map = new IdentityMap;
        $metadataRegistry = self::buildMetadataregistry($config, $devMode);

        foreach ($metadataRegistry->getMetadatas() as $meta) {
            $map->addClass($meta->getClass());

            if ($meta->hasAlias()) {
                $map->addAlias($meta->getAlias(), $meta->getClass());
            }
        }

        $proxyConfig = new ProxyConfig;

        if ($devMode === true) {
            $path = $config['cache'] . self::PROXIES_DIRECTORY;
            $filesystem = new Filesystem;

            if (!$filesystem->exists($path)) {
                $filesystem->mkdir($path);
            }

            $proxyConfig->setProxiesTargetDir($path);
            spl_autoload_register($proxyConfig->getProxyAutoloader());
        }

        $conf
            ->setIdentityMap($map)
            ->setMetadataRegistry($metadataRegistry)
            ->setProxyFactory(new LazyLoadingGhostFactory($proxyConfig));

        return $conf;
    }

    /**
     * Set the identity map
     *
     * @param IdentityMap $map
     *
     * @return Configuration self
     */
    protected function setIdentityMap(IdentityMap $map)
    {
        $this->identityMap = $map;

        return $this;
    }

    /**
     * Return the identity map
     *
     * @return IdentityMap
     */
    public function getIdentityMap()
    {
        return $this->identityMap;
    }

    /**
     * Set the metadata registry
     *
     * @param MetadataRegistry $registry
     *
     * @return Configuration self
     */
    protected function setMetadataRegistry(MetadataRegistry $registry)
    {
        $this->metadataRegistry = $registry;

        return $this;
    }

    /**
     * Return the metadata registry
     *
     * @return MetadataRegistry
     */
    public function getMetadataRegistry()
    {
        return $this->metadataRegistry;
    }

    /**
     * Set the repository factory
     *
     * @param RepositoryFactory $factory
     *
     * @return Configuration self
     */
    public function setRepositoryFactory(RepositoryFactory $factory)
    {
        $this->repositoryFactory = $factory;

        return $this;
    }

    /**
     * Return the repository factory
     *
     * @return RepositoryFactory
     */
    public function getRepositoryFactory()
    {
        return $this->repositoryFactory;
    }

    /**
     * Set the lazy loading factory
     *
     * @param LazyLoadingGhostFactory $proxyFactory
     *
     * @return Configuration self
     */
    protected function setProxyFactory(
        LazyLoadingGhostFactory $proxyFactory
    ) {
        $this->proxyFactory = $proxyFactory;

        return $this;
    }

    /**
     * Return the lazy loading factory
     *
     * @return LazyLoadingGhostFactory
     */
    public function getProxyFactory()
    {
        return $this->proxyFactory;
    }

    /**
     * Return the metadata registry either via cache or rebuilt from conf files
     *
     * @param array $config
     * @param bool $devMode
     *
     * @return MetadataRegistry
     */
    protected static function buildMetadataregistry(array $config, $devMode)
    {
        $path = $config['cache'] . self::METADATA_CACHE_FILE;
        $cache = new ConfigCache($path, $devMode);

        if (!$cache->isFresh()) {
            $metadataRegistry = new MetadataRegistry;
            $reader = Readers::getReader($config['reader']);
            $resources = [];

            foreach ($config['locations'] as $location) {
                $metas = $reader->load($location);

                foreach ($metas as $meta) {
                    $metadataRegistry->addMetadata($meta);
                }

                foreach ($reader->getResources($location) as $resource) {
                    $resources[] = new FileResource($resource);
                }
            }

            $code = MetadataRegistryCacheBuilder::getCode($metadataRegistry);

            $cache->write($code, $resources);

            return $metadataRegistry;
        }

        return require $path;
    }

    /**
     * Return an option resolver to validate the passed config data
     *
     * @return OptionsResolver
     */
    protected static function buildOptionsResolver()
    {
        $resolver = new OptionsResolver;
        $resolver->setRequired(['cache', 'reader', 'locations']);
        $resolver->setAllowedTypes('cache', 'string');
        $resolver->setAllowedTypes('reader', 'string');
        $resolver->setAllowedTypes('locations', 'array');
        $resolver->setNormalizer('cache', function($options, $value) {
            return rtrim($value, '/');
        });

        return $resolver;
    }
}
