<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests;

use Innmind\Neo4j\ONM\Configuration;
use Symfony\Component\{
    Config\Definition\Processor,
    Yaml\Yaml
};

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    private $c;
    private $f;

    public function setUp()
    {
        $this->c = new Configuration;
        $this->f = new Processor;
    }

    public function testProcess()
    {
        $result = $this->f->processConfiguration(
            $this->c,
            [$expected = Yaml::parse(file_get_contents('fixtures/mapping.yml'))]
        );

        //the processor automatically add them
        $expected['SomeRelationship']['labels'] = [];
        $expected['SomeRelationship']['children'] = [];

        $this->assertSame($expected, $result);
    }
}
