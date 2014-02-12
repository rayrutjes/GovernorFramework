<?php

namespace Governor\Framework\EventStore;

use Governor\Framework\Eventing\EventMessageBus;
use Governor\Framework\AggregateRoot;
use Governor\Framework\Repository;
use Governor\Framework\AggregateRootNotFoundException;

use Rhumsaa\Uuid\Uuid;

class EventSourceRepository implements Repository
{
    private $eventStore;
    private $eventBus;
    private $streams = array();

    public function __construct(EventStore $eventStore, EventBus $eventBus)
    {
        $this->eventStore = $eventStore;
        $this->eventBus = $eventBus;
    }

    /**
     * @return AggregateRoot
     */
    public function find($className, Uuid $uuid, $expectedVersion = null)
    {
        try {
            $eventStream = $this->eventStore->find($uuid);
        } catch (EventStreamNotFoundException $e) {
            throw new AggregateRootNotFoundException();
        }

        $this->streams[(string)$uuid] = $eventStream;

        $aggregateRootClass = $eventStream->getClassName();

        if ($aggregateRootClass !== ltrim($className, '\\')) {
            throw new AggregateRootNotFoundException();
        }

        if ($expectedVersion && $eventStream->getVersion() !== $expectedVersion) {
            throw new ConcurrencyException();
        }

        $reflClass = new \ReflectionClass($aggregateRootClass);

        $aggregateRoot = $reflClass->newInstanceWithoutConstructor();
        $aggregateRoot->loadFromEventStream($eventStream);

        return $aggregateRoot;
    }

    /**
     * @return void
     */
    public function save(AggregateRoot $object)
    {
        $id = (string)$object->getId();

        if (!isset($this->streams[$id])) {
            $this->streams[$id] = new DomainEventStream(
                get_class($object),
                $object->getId()
            );
        }

        $eventStream = $this->streams[$id];
        $eventStream->addEvents($object->pullDomainEvents());

        $transaction = $this->eventStore->commit($eventStream);

        foreach ($transaction->getCommittedEvents() as $event) {
            $event->setAggregateId($object->getId());
            $this->eventBus->publish($event);
        }
    }
}