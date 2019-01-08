<?php
declare(strict_types = 1);

namespace Tests\Innmind\Neo4j\ONM\Specification;

use Innmind\Neo4j\ONM\Specification\ConvertSign;
use Innmind\Specification\Sign;
use PHPUnit\Framework\TestCase;

class ConvertSignTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInterface($sign, $expected)
    {
        $convert = new ConvertSign;

        $this->assertSame($expected, $convert(Sign::$sign()));
    }

    public function cases(): array
    {
        return [
            ['equality', '='],
            ['inequality', '<>'],
            ['lessThan', '<'],
            ['moreThan', '>'],
            ['lessThanOrEqual', '<='],
            ['moreThanOrEqual', '>='],
            ['isNull', 'IS NULL'],
            ['isNotNull', 'IS NOT NULL'],
            ['startsWith', 'STARTS WITH'],
            ['endsWith', 'ENDS WITH'],
            ['contains', '=~'],
            ['in', 'IN'],
        ];
    }
}
