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

use Doctrine\DBAL\Driver\Statement;
use Ramsey\Uuid\Uuid;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\BaseEvent;
use TYPO3\CMS\EventSourcing\Infrastructure\EventStore\Updatable;

class SqlDriverIterator implements \Iterator, EventTraversable
{
    /**
     * @param Statement $statement
     * @return SqlDriverIterator
     */
    public static function create(Statement $statement)
    {
        return GeneralUtility::makeInstance(SqlDriverIterator::class, $statement);
    }

    /**
     * @var Statement
     */
    protected $statement;

    /**
     * @var BaseEvent
     */
    protected $event;

    /**
     * @var Updatable[]
     */
    private $updates;

    public function __construct(Statement $statement, Updatable ...$updates)
    {
        $this->statement = $statement;
        $this->updates = $updates;
        $this->reconstituteNext();
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
        // ignore rewind
    }

    /**
     * @return bool
     */
    protected function reconstituteNext()
    {
        $rawEvent = $this->statement->fetch();
        if (!$rawEvent) {
            return $this->invalidate();
        }

        $eventClassName = $rawEvent['event_name'];

        // @todo microsecond part is omitted if fetching from database
        $eventDate = new \DateTime($rawEvent['event_date']);

        $aggregateId = null;
        $data = $rawEvent['data'];
        $metadata = $rawEvent['metadata'];

        if (!empty($rawEvent['aggregate_id'])) {
            $aggregateId = Uuid::fromString($rawEvent['aggregate_id']);
        }
        if ($data !== null) {
            $data = json_decode($data, true);
        }
        if ($metadata !== null) {
            $metadata = json_decode($metadata, true);
        }

        foreach ($this->updates as $update) {
            if (in_array($eventClassName, $update->listensTo(), true)) {
                $event = $update->update(
                    $eventClassName,
                    $rawEvent['event_id'],
                    $rawEvent['event_version'],
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

        if (empty($event)) {
            /** @var BaseEvent $eventClassName */
            $event = $eventClassName::reconstitute(
                $eventClassName,
                $rawEvent['event_id'],
                $rawEvent['event_version'],
                $eventDate,
                $aggregateId,
                $data,
                $metadata
            );
        }

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
}
