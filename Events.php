<?php
declare(strict_types = 1);

namespace Innmind\Neo4j\ONM;

class Events
{
    const PRE_PERSIST = 'innmind.neo4j.onm.pre.persist';
    const POST_PERSIST = 'innmind.neo4j.onm.post.persist';
    const PRE_REMOVE = 'innmind.neo4j.onm.pre.remove';
    const POST_REMOVE = 'innmind.neo4j.onm.post.remove';
}
