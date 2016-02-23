<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Tests\Metadata;

use Innmind\Neo4j\ONM\Metadata\Identity;

class IdentityTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $i = new Identity('uuid');

        $this->assertSame('uuid', (string) $i);
    }
}
