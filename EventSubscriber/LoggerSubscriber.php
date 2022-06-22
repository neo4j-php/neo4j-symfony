<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\EventSubscriber;

use Neo4j\Neo4jBundle\Collector\QueryLogger;
use Neo4j\Neo4jBundle\Events\FailureEvent;
use Neo4j\Neo4jBundle\Events\PostRunEvent;
use Neo4j\Neo4jBundle\Events\PreRunEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LoggerSubscriber implements EventSubscriberInterface
{
    private QueryLogger $queryLogger;

    public function __construct(QueryLogger $queryLogger)
    {
        $this->queryLogger = $queryLogger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            PreRunEvent::EVENT_ID => 'onPreRun',
            PostRunEvent::EVENT_ID => 'onPostRun',
            FailureEvent::EVENT_ID => 'onFailure',
        ];
    }

    public function onPreRun(PreRunEvent $event): void
    {
        foreach ($event->getStatements() as $statement) {
            $this->queryLogger->record($statement);
        }
    }

    public function onPostRun(PostRunEvent $event): void
    {
        foreach ($event->getResults() as $result) {
            $this->queryLogger->finish($result);
        }
    }

    public function onFailure(FailureEvent $event): void
    {
        $this->queryLogger->logException($event->getException());
    }
}
