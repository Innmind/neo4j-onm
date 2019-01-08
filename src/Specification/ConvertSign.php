<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM\Specification;

use Innmind\Specification\Sign;

final class ConvertSign
{
    public function __invoke(Sign $sign): string
    {
        switch (true) {
            case $sign->equals(Sign::contains()):
                return '=~';
        }

        return (string) $sign;
    }
}
