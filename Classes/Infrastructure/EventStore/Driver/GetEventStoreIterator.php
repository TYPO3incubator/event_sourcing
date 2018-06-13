<?php
namespace TYPO3\CMS\EventSourcing\Infrastructure\EventStore\Driver;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use EventStore\StreamFeed\StreamFeedIterator;
use Ramsey\Uuid\Uuid;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\EventSourcing\Infrastructure\EventStore\Updatable;

class GetEventStoreIterator implements \Iterator, EventTraversable
{
    /**
     * @param StreamFeedIterator $feedIterator
     * @return GetEventStoreIterator
     */
    public static function create(StreamFeedIterator $feedIterator)
    {
        return GeneralUtility::makeInstance(GetEventStoreIterator::class, $feedIterator);
    }

    /**
     * @var StreamFeedIterator
     */
    protected $feedIterator;

    /**
     * @var Updatable[]
     */
    private $updates;

    /**
     * @var BaseEvent
     */
    protected $event;

    public function __construct(StreamFeedIterator $feedIterator, Updatable ...$updates)
    {
        $this->feedIterator = $feedIterator;
        $this->updates = $updates;
    }

    /**
     * @return null|false|array
     */
    public function current()
    {
        return $this->event;
    }

    /**
     * @return null|string
     */
    public function key()
    {
        return ($this->event->getEventId() ?? null);
    }

    public function next()
    {
        $this->reconstituteNext();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return ($this->event !== null);
    }

    public function rewind()
    {
        $this->feedIterator->rewind();
        $this->reconstituteNext();
    }

    /**
     * @return bool
     */
    protected function reconstituteNext()
    {
        if (!$this->feedIterator->valid()) {
            return $this->invalidate();
        }

        /** @var \EventStore\StreamFeed\EntryWithEvent $item */
        $item = $this->feedIterator->current();
        $this->feedIterator->next();

        $eventClassName = $item->getEvent()->getType();

        $entryData = $this->extractPrivateProperty($item->getEntry(), 'json');
        $eventDate = new \DateTime($entryData['updated']);

        $aggregateId = null;
        $data = $item->getEvent()->getData();
        $metadata = $item->getEvent()->getMetadata();

        if (!empty($metadata['$aggregateId'])) {
            $aggregateId = Uuid::fromString($metadata['$aggregateId']);
            unset($metadata['$aggregateId']);
        }

        foreach ($this->updates as $update) {
            if (in_array($eventClassName, $update->listensTo(), true)) {
                $event = $update->update(
                    $eventClassName,
                    $item->getEvent()->getEventId()->toNative(),
                    $item->getEvent()->getVersion(),
                    $eventDate,
                    $aggregateId,
                    $data,
                    $metadata
                );
                if ($event !== null) {
                    break;
                }
            }
        }

        /** @var BaseEvent $eventClassName */
        $event = $eventClassName::reconstitute(
            $item->getEvent()->getType(),
            $item->getEvent()->getEventId()->toNative(),
            $item->getEvent()->getVersion(),
            $eventDate,
            $aggregateId,
            $data,
            $metadata
        );

        if (!$event instanceof BaseEvent) {
            return $this->invalidate();
        }

        $this->event = $event;
        return true;
    }

    /**
     * @return bool
     */
    protected function invalidate()
    {
        $this->event = null;
        return false;
    }

    /**
     * @param object $subject
     * @param string $propertyName
     * @return null|mixed
     */
    protected function extractPrivateProperty($subject, string $propertyName)
    {
        $reflection = new \ReflectionClass($subject);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);

        foreach ($properties as $property) {
            if ($property->getName() !== $propertyName) {
                continue;
            }
            $property->setAccessible(true);
            return $property->getValue($subject);
        }

        return null;
    }
}
