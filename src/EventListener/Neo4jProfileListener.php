<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\EventListener;

use Laudis\Neo4j\Databags\ResultSummary;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Exception\Neo4jException;
use Neo4j\Neo4jBundle\Event\FailureEvent;
use Neo4j\Neo4jBundle\Event\PostRunEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Service\ResetInterface;

final class Neo4jProfileListener implements EventSubscriberInterface, ResetInterface
{
    /**
     * @var list<ResultSummary>
     */
    private array $profiledSummaries = [];

    /**
     * @var list<array{exception: Neo4jException, statement: Statement, alias: string|null}>
     */
    private array $profiledFailures = [];

    /**
     * @param list<string> $enabledProfiles
     */
    public function __construct(private array $enabledProfiles = [])
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PostRunEvent::EVENT_ID => 'onPostRun',
            FailureEvent::EVENT_ID => 'onFailure',
        ];
    }

    public function onPostRun(PostRunEvent $event): void
    {
        if (in_array($event->getAlias(), $this->enabledProfiles)) {
            $this->profiledSummaries[] = $event->getResult();
        }
    }

    public function onFailure(FailureEvent $event): void
    {
        if (in_array($event->getAlias(), $this->enabledProfiles)) {
            $this->profiledFailures[] = [
                'exception' => $event->getException(),
                'statement' => $event->getStatement(),
                'alias' => $event->getAlias(),
            ];
        }
    }

    public function getProfiledSummaries(): array
    {
        return $this->profiledSummaries;
    }

    /**
     * @return list<array{exception: Neo4jException, statement: Statement, alias: string|null}>
     */
    public function getProfiledFailures(): array
    {
        return $this->profiledFailures;
    }

    public function reset(): void
    {
        $this->profiledFailures = [];
        $this->profiledSummaries = [];
    }
}
