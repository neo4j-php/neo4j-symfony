<?php

namespace Neo4j\Neo4jBundle;

use Laudis\Neo4j\Exception\Neo4jException;
use Laudis\Neo4j\Types\CypherList;
use Neo4j\Neo4jBundle\Events\FailureEvent;
use Neo4j\Neo4jBundle\Events\PostRunEvent;
use Neo4j\Neo4jBundle\Events\PreRunEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventHandler
{
    private ?EventDispatcherInterface $dispatcher;

    public function __construct(?EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function handle(callable $runHandler, iterable $statements): ?CypherList
    {
        if (null === $this->dispatcher) {
            return $runHandler();
        }

        $this->dispatcher->dispatch(new PreRunEvent($statements), PreRunEvent::EVENT_ID);

        try {
            $tbr = $runHandler();
            $this->dispatcher->dispatch(new PostRunEvent($tbr), PostRunEvent::EVENT_ID);
        } catch (Neo4jException $e) {
            $event = new FailureEvent($e);
            $event = $this->dispatcher->dispatch($event, FailureEvent::EVENT_ID);

            if ($event->shouldThrowException()) {
                throw $e;
            }
        }

        return null;
    }
}
