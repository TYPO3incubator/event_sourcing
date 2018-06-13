<?php
namespace TYPO3\CMS\EventSourcing\Infrastructure\EventStore;

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

use Ramsey\Uuid\UuidInterface;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Base\Event\BaseEvent;

interface Updatable
{
    /**
     * @return string[]
     */
    public function listensTo();

    /**
     * @param string $eventType
     * @param string $eventId
     * @param int $eventVersion
     * @param \DateTime $date
     * @param UuidInterface|null $aggregateId
     * @param null|array $data
     * @param null|array $metadata
     * @return BaseEvent
     */
    public function update(
        string $eventType,
        string $eventId,
        int $eventVersion,
        \DateTime $date,
        UuidInterface $aggregateId = null,
        $data,
        $metadata
    ): ?BaseEvent;
}
