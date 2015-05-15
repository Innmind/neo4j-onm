<?php

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateConfiguration()
    {
        $conf = Configuration::create(
            [
                'cache' => 'cache',
                'reader' => 'yaml',
                'locations' => ['fixtures']
            ],
            true
        );

        $this->assertInstanceof(
            'Innmind\\Neo4j\\ONM\\Configuration',
            $conf
        );
        $this->assertEquals(
            2,
            count($conf->getMetadataRegistry()->getMetadatas())
        );
        $this->assertEquals(
            'RF',
            $conf->getIdentityMap()->getAlias('Referer')
        );
    }
}
