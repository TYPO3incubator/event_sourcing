<?php
namespace TYPO3\CMS\EventSourcing\Core\Database;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\EventSourcing\Core\Domain\Model\Meta\EventSourcingMap;
use TYPO3\CMS\EventSourcing\Core\Service\DatabaseService;

/**
 * Extends to regular connection pool by intercepting the default
 * connection and providing a specific local storage instead. Besides
 * that, origin connection (prior default connection) can be accessed.
 */
class ConnectionPool extends \TYPO3\CMS\Core\Database\ConnectionPool
{
    const ORIGIN_CONNECTION_NAME = 'Origin';

    /**
     * @var array
     */
    private static $mapping = [];

    /**
     * @var bool
     */
    private static $originAsDefault = false;

    /**
     * @param string $sourceName
     * @param string|null $targetName
     */
    public static function map(string $sourceName, string $targetName = null)
    {
        if ($targetName === null) {
            unset(self::$mapping[$sourceName]);
        } else {
            self::$mapping[$sourceName] = $targetName;
        }
    }

    /**
     * Defines whether to use origin as default.
     *
     * This might be useful if setting up test-cases and install
     * tool modifications - thus, local storage can be overruled.
     *
     * @param bool $originAsDefault
     * @return bool
     */
    public static function originAsDefault(bool $originAsDefault)
    {
        $currentValue = self::$originAsDefault;
        self::$originAsDefault = $originAsDefault;
        return $currentValue;
    }

    /**
     * @return ConnectionPool
     */
    public static function instance()
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * Creates a connection object based on the specified table name.
     *
     * @param string $tableName
     * @return Connection
     */
    public function getConnectionForTable(string $tableName): Connection
    {
        // use origin for
        // + tables that shall not be projected
        // + tables that do not belong to caching framework
        $useOriginConnection = (
            !EventSourcingMap::provide()->shallProject($tableName)
            && !DatabaseService::instance()->isCacheTable($tableName)
        );

        if ($useOriginConnection) {
            return $this->getOriginConnection();
        }

        return parent::getConnectionForTable($tableName);
    }

    /**
     * Creates a connection object based on the specified identifier.
     *
     * @param string $connectionName
     * @return Connection
     */
    public function getConnectionByName(string $connectionName): Connection
    {
        if (
            self::$originAsDefault
            && $connectionName === static::DEFAULT_CONNECTION_NAME
        ) {
            $connectionName = static::ORIGIN_CONNECTION_NAME;
        } elseif (isset(self::$mapping[$connectionName])) {
            $connectionName = self::$mapping[$connectionName];
        }

        return parent::getConnectionByName($connectionName);
    }

    /**
     * Gets the origin connection, previously know as default connection.
     *
     * @return Connection
     */
    public function getOriginConnection(): Connection
    {
        return $this->getConnectionByName(static::ORIGIN_CONNECTION_NAME);
    }

    /**
     * Gets a new query build instance for origin connection.
     *
     * @return QueryBuilder
     */
    public function getOriginQueryBuilder(): QueryBuilder
    {
        return $this->getOriginConnection()->createQueryBuilder();
    }

    /**
     * Defines a particular connection to act as default connection.
     *
     * @param string $defaultConnectionName
     */
    public function setDefaultConnectionName(string $defaultConnectionName)
    {
        static::map(static::DEFAULT_CONNECTION_NAME, $defaultConnectionName);
    }
}
