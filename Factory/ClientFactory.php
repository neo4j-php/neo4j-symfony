<?php

declare(strict_types=1);

namespace Neo4jCommunity\Neo4jBundle\Factory;

use GraphAware\Neo4j\Client\ClientBuilder;
use GraphAware\Neo4j\Client\ClientInterface;
use GraphAware\Neo4j\Client\Event\FailureEvent;
use GraphAware\Neo4j\Client\Event\PostRunEvent;
use GraphAware\Neo4j\Client\Event\PreRunEvent;
use GraphAware\Neo4j\Client\Neo4jClientEvents;
use Neo4jCommunity\Neo4jBundle\Collector\DebugLogger;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class ClientFactory
{
    /**
     * @var DebugLogger
     */
    private $debugLogger;

    /**
     * @param DebugLogger|null $debugLogger
     */
    public function __construct(DebugLogger $debugLogger = null)
    {
        $this->debugLogger = $debugLogger;
    }

    /**
     * Build an Client form multiple URLs.
     *
     * @param array $urls
     *
     * @return ClientInterface
     */
    public function create(array $urls): ClientInterface
    {
        $defaultUrl = array_shift($urls);
        $builder = ClientBuilder::create()
            ->addConnection('default', $defaultUrl);

        foreach ($urls as $i => $url) {
            $builder->addConnection('url'.$i, $url);
        }

        if ($logger = $this->debugLogger) {
            $this->registerEvents($builder);
        }

        return $builder->build();
    }

    /**
     * @param ClientBuilder $builder
     */
    private function registerEvents(ClientBuilder $builder)
    {
        $logger = $this->debugLogger;
        $builder->registerEventListener(
            Neo4jClientEvents::NEO4J_PRE_RUN,
            function (PreRunEvent $event) use ($logger) {
                foreach ($event->getStatements() as $statement) {
                    $logger->addStatement($statement);
                }
            }
        );

        $builder->registerEventListener(
            Neo4jClientEvents::NEO4J_POST_RUN,
            function (PostRunEvent $event) use ($logger) {
                foreach ($event->getResults() as $result) {
                    $logger->addResult($result);
                }
            }
        );

        $builder->registerEventListener(
            Neo4jClientEvents::NEO4J_ON_FAILURE,
            function (FailureEvent $event) use ($logger) {
                $logger->addException($event->getException());
            }
        );
    }
}
