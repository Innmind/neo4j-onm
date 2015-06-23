<?php

namespace Innmind\Neo4j\ONM;

class Events
{
    const PRE_PERSIST = 'innmind.neo4j.onm.pre.persist';
    const POST_PERSIST = 'innmind.neo4j.onm.post.persist';
    const PRE_UPDATE = 'innmind.neo4j.onm.pre.update';
    const POST_UPDATE = 'innmind.neo4j.onm.post.update';
    const PRE_REMOVE = 'innmind.neo4j.onm.pre.remove';
    const POST_REMOVE = 'innmind.neo4j.onm.post.remove';
    const PRE_FLUSH = 'innmind.neo4j.onm.pre.flush';
    const POST_FLUSH = 'innmind.neo4j.onm.post.flush';
    const PRE_QUERY = 'innmind.neo4j.onm.pre.query';
    const POST_QUERY = 'innmind.neo4j.onm.post.query';
}
