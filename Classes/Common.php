<?php
namespace TYPO3\CMS\EventSourcing;

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

use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\EventStore\Driver\GetEventStoreDriver;
use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\EventStore\Driver\SqlDriver;
use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\EventStore\EventStore;
use TYPO3\CMS\EventSourcing\DataHandling\Infrastructure\EventStore\EventStorePool;

class Common
{
    public static function registerEventSources()
    {
        // initialize default EventStore using SqlDriver
        EventStorePool::provide()
            ->enrolStore('sql')
            ->concerning('*')
            ->setStore(
                EventStore::create(
                    SqlDriver::instance()
                )
            );
//        EventStorePool::provide()
//            ->enrolStore('geteventstore.com')
//            ->concerning('*')
//            ->setStore(
//                EventStore::create(
//                    GetEventStoreDriver::create('http://127.0.0.1:2113', 'admin', 'changeit', true)
//                )
//            );
    }
}
