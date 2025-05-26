<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests;

use Neo4j\Neo4jBundle\Tests\App\TestKernel;

final class Application extends \Symfony\Bundle\FrameworkBundle\Console\Application
{
    public function __construct()
    {
        parent::__construct(new TestKernel('test', true));
    }
}
