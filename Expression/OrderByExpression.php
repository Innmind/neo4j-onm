<?php

namespace Innmind\Neo4j\ONM\Expression;

use Innmind\Neo4j\ONM\ExpressionInterface;

class OrderByExpression implements ExpressionInterface
{
    protected $content;
    protected $direction;

    /**
     * @param string $content
     * @param string $direction
     */
    public function __construct($content, $direction = 'ASC')
    {
        $this->content = (string) $content;
        $this->direction = (string) $direction === 'DESC' ? 'DESC' : 'ASC';
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return sprintf(
            '%s %s',
            $this->content,
            $this->direction
        );
    }
}
