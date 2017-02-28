<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\EventSubscriber;

use GraphAware\Neo4j\Client\Event\FailureEvent;
use GraphAware\Neo4j\Client\Event\PostRunEvent;
use GraphAware\Neo4j\Client\Event\PreRunEvent;
use GraphAware\Neo4j\Client\Neo4jClientEvents;
use Neo4j\Neo4jBundle\Collector\QueryLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoggerSubscriber implements EventSubscriberInterface
{
    /**
     * @var QueryLogger
     */
    private $queryLogger;

    /**
     * @param QueryLogger $queryLogger
     */
    public function __construct(QueryLogger $queryLogger)
    {
        $this->queryLogger = $queryLogger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Neo4jClientEvents::NEO4J_PRE_RUN => 'onPreRun',
            Neo4jClientEvents::NEO4J_POST_RUN => 'onPostRun',
            Neo4jClientEvents::NEO4J_ON_FAILURE => 'onFailure',
        ];
    }

    /**
     * @param PreRunEvent $event
     */
    public function onPreRun(PreRunEvent $event)
    {
        foreach ($event->getStatements() as $statement) {
            $this->queryLogger->record($statement);
        }
    }

    /**
     * @param PostRunEvent $event
     */
    public function onPostRun(PostRunEvent $event)
    {
        foreach ($event->getResults() as $result) {
            $this->queryLogger->finish($result);
        }
    }

    /**
     * @param FailureEvent $event
     */
    public function onFailure(FailureEvent $event)
    {
        $this->queryLogger->logException($event->getException());
    }
}
