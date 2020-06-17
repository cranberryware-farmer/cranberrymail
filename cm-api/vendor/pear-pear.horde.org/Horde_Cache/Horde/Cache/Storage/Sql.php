<?php
/**
 * Copyright 2007-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */

/**
 * Cache storage in a SQL databsae.
 *
 * The table structure for the cache is as follows:
 * <pre>
 * CREATE TABLE horde_cache (
 *     cache_id          VARCHAR(32) NOT NULL,
 *     cache_timestamp   BIGINT NOT NULL,
 *     cache_data        LONGBLOB,
 *     (Or on PostgreSQL:)
 *     cache_data        TEXT,
 *     (Or on some other DBMS systems:)
 *     cache_data        IMAGE,
 *
 *     PRIMARY KEY (cache_id)
 * );
 * </pre>
 *
 * @author    Ben Klang <ben@alkaloid.net>
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2007-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Cache
 */
class Horde_Cache_Storage_Sql extends Horde_Cache_Storage_Base
{
    /**
     * Handle for the current database connection.
     *
     * @var Horde_Db_Adapter
     */
    protected $_db;

    /**
     * Constructor.
     *
     * @param array $params  Parameters:
     * <pre>
     *   - db: (Horde_Db_Adapter) [REQUIRED] The DB instance.
     *   - table: (string) The name of the cache table.
     *            DEFAULT: 'horde_cache'
     * </pre>
     */
    public function __construct($params = array())
    {
        if (!isset($params['db'])) {
            throw new InvalidArgumentException('Missing db parameter.');
        }

        parent::__construct(array_merge(array(
            'table' => 'horde_cache',
        ), $params));
    }

    /**
     */
    protected function _initOb()
    {
        $this->_db = $this->_params['db'];
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        /* Only do garbage collection 0.1% of the time we create an object. */
        if (substr(time(), -3) !== '000') {
            return;
        }

        $query = 'DELETE FROM ' . $this->_params['table'] .
                 ' WHERE cache_expiration < ? AND cache_expiration <> 0';
        $values = array(time());

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {}
    }

    /**
     */
    public function get($key, $lifetime = 0)
    {
        $okey = $key;
        $key = hash('md5', $key);

        $timestamp = time();
        $maxage = $timestamp - $lifetime;

        /* Build SQL query. */
        $query = 'SELECT cache_data FROM ' . $this->_params['table'] .
                 ' WHERE cache_id = ?';
        $values = array($key);

        // 0 lifetime checks for objects which have no expiration
        if ($lifetime != 0) {
            $query .= ' AND cache_timestamp >= ?';
            $values[] = $maxage;
        }

        try {
            $result = $this->_db->selectValue($query, $values);
            $columns = $this->_db->columns($this->_params['table']);
        } catch (Horde_Db_Exception $e) {
            return false;
        }

        if (!$result) {
            /* No rows were found - cache miss */
            if ($this->_logger) {
                $this->_logger->log(sprintf('Cache miss: %s (Id %s newer than %d)', $okey, $key, $maxage), 'DEBUG');
            }
            return false;
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Cache hit: %s (Id %s newer than %d)', $okey, $key, $maxage), 'DEBUG');
        }

        return $columns['cache_data']->binaryToString($result);
    }

    /**
     */
    public function set($key, $data, $lifetime = 0)
    {
        $okey = $key;
        $key = hash('md5', $key);

        $timestamp = time();

        // 0 lifetime indicates the object should not be GC'd.
        $expiration = ($lifetime === 0)
            ? 0
            : ($lifetime + $timestamp);

        if ($this->_logger) {
            $this->_logger->log(sprintf('Cache set: %s (Id %s set at %d expires at %d)', $okey, $key, $timestamp, $expiration), 'DEBUG');
        }

        // Remove any old cache data and prevent duplicate keys
        $query = 'DELETE FROM ' . $this->_params['table'] . ' WHERE cache_id = ?';
        $values = array($key);
        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {}

        /* Build SQL query. */
        $values = array(
            'cache_id' => $key,
            'cache_timestamp' => $timestamp,
            'cache_expiration' => $expiration,
            'cache_data' => new Horde_Db_Value_Binary($data)
        );

        try {
            $this->_db->insertBlob($this->_params['table'], $values);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Cache_Exception($e);
        }
    }

    /**
     */
    public function exists($key, $lifetime = 0)
    {
        $okey = $key;
        $key = hash('md5', $key);

        /* Build SQL query. */
        $query = 'SELECT 1 FROM ' . $this->_params['table'] .
                 ' WHERE cache_id = ?';
        $values = array($key);

        // 0 lifetime checks for objects which have no expiration
        if ($lifetime != 0) {
            $query .= ' AND cache_timestamp >= ?';
            $values[] = time() - $lifetime;
        }

        try {
            $result = $this->_db->selectValue($query, $values);
        } catch (Horde_Db_Exception $e) {
            return false;
        }

        $timestamp = time();
        if (empty($result)) {
            if ($this->_logger) {
                $this->_logger->log(sprintf('Cache exists() miss: %s (Id %s newer than %d)', $okey, $key, $timestamp), 'DEBUG');
            }
            return false;
        }

        if ($this->_logger) {
            $this->_logger->log(sprintf('Cache exists() hit: %s (Id %s newer than %d)', $okey, $key, $timestamp), 'DEBUG');
        }

        return true;
    }

    /**
     */
    public function expire($key)
    {
        $key = hash('md5', $key);

        $query = 'DELETE FROM ' . $this->_params['table'] .
                 ' WHERE cache_id = ?';
        $values = array($key);

        try {
            $this->_db->delete($query, $values);
        } catch (Horde_Db_Exception $e) {
            return false;
        }

        return true;
    }

    /**
     */
    public function clear()
    {
        $query = 'DELETE FROM ' . $this->_params['table'];

        try {
            $this->_db->delete($query);
        } catch (Horde_Db_Exception $e) {
            throw new Horde_Cache_Exception($e);
        }
    }

}
