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
     * @var list<array{
     *     result: ResultSummary,
     *     alias: string|null,
     *     time: string,
     *     start_time: float|int,
     *     end_time: float|int
     * }>
     */
    private array $profiledSummaries = [];

    /**
     * @var list<array{
     *     exception: Neo4jException,
     *     statement: ?Statement,
     *     alias: string|null,
     *     time: string,
     *     timestamp: int
     * }>
     */
    private array $profiledFailures = [];

    /**
     * @param list<string> $enabledProfiles
     */
    public function __construct(private readonly array $enabledProfiles = [])
    {
    }

    #[\Override]
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
            $time = $event->getTime();
            $result = $event->getResult();
            $end_time = $time->getTimestamp() + $result->getResultAvailableAfter() + $result->getResultConsumedAfter();
            $this->profiledSummaries[] = [
                'result' => $event->getResult(),
                'alias' => $event->getAlias(),
                'time' => $time->format('Y-m-d H:i:s'),
                'start_time' => $time->getTimestamp(),
                'end_time' => $end_time,
            ];
        }
    }

    public function onFailure(FailureEvent $event): void
    {
        if (in_array($event->getAlias(), $this->enabledProfiles)) {
            $time = $event->getTime();
            $this->profiledFailures[] = [
                'exception' => $event->getException(),
                'statement' => $event->getStatement(),
                'alias' => $event->getAlias(),
                'time' => $time->format('Y-m-d H:i:s'),
                'timestamp' => $time->getTimestamp(),
            ];
        }
    }

    public function getProfiledSummaries(): array
    {
        return $this->profiledSummaries;
    }

    /**
     * @return list<array{
     *     exception: Neo4jException,
     *     statement: ?Statement,
     *     alias: string|null,
     *     time: string,
     *     timestamp: int
     * }>
     */
    public function getProfiledFailures(): array
    {
        return $this->profiledFailures;
    }

    #[\Override]
    public function reset(): void
    {
        $this->profiledFailures = [];
        $this->profiledSummaries = [];
    }
}
