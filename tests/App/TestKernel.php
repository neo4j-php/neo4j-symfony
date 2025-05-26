<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Tests\App;

use Neo4j\Neo4jBundle\Neo4jBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    #[\Override]
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new WebProfilerBundle(),
            new Neo4jBundle(),
        ];
    }

    #[\Override]
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/config/default.yml');
        if ('ci' === $this->environment) {
            $loader->load(__DIR__.'/config/ci/default.yml');
        }
    }
}
