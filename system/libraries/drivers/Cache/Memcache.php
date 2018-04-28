<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * Memcache-based Cache driver.
 *
 * $Id$
 *
 * @package    Cache
 * @author     Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class Cache_Memcache_Driver extends Cache_Driver
{
    protected $config;
    protected $backend;
    protected $flags;

    public function __construct($config)
    {
        if (! extension_loaded('memcache')) {
            throw new Cache_Exception('The memcache PHP extension must be loaded to use this driver.');
        }

        ini_set('memcache.allow_failover', (isset($config['allow_failover']) and $config['allow_failover']) ? true : false);

        $this->config = $config;
        $this->backend = new Memcache;

        $this->flags = (isset($config['compression']) and $config['compression']) ? MEMCACHE_COMPRESSED : false;

        foreach ($config['servers'] as $server) {
            // Make sure all required keys are set
            $server += [
                'host'           => '127.0.0.1',
                'port'           => 11211,
                'persistent'     => false,
                'weight'         => 1,
                'timeout'        => 1,
                'retry_interval' => 15
            ];

            // Add the server to the pool
            $this->backend->addServer($server['host'], $server['port'], (bool) $server['persistent'], (int) $server['weight'], (int) $server['timeout'], (int) $server['retry_interval'], true, [$this, '_memcache_failure_callback']);
        }
    }

    public function _memcache_failure_callback($host, $port)
    {
        $this->backend->setServerParams($host, $port, 1, -1, false);
        Kohana_Log::add('error', __('Cache: Memcache server down: :host:::port:', [':host:' => $host, ':port:' => $port]));
    }

    public function set($items, $tags = null, $lifetime = null)
    {
        if ($lifetime !== 0) {
            // Memcache driver expects unix timestamp
            $lifetime += time();
        }

        if ($tags !== null) {
            throw new Cache_Exception('Memcache driver does not support tags');
        }

        foreach ($items as $key => $value) {
            if (is_resource($value)) {
                throw new Cache_Exception('Caching of resources is impossible, because resources cannot be serialised.');
            }

            if (! $this->backend->set($key, $value, $this->flags, $lifetime)) {
                return false;
            }
        }

        return true;
    }

    public function get($keys, $single = false)
    {
        $items = $this->backend->get($keys);

        if ($single) {
            if ($items === false) {
                return null;
            }

            return (count($items) > 0) ? current($items) : null;
        } else {
            return ($items === false) ? [] : $items;
        }
    }

    /**
     * Get cache items by tag
     */
    public function get_tag($tags)
    {
        throw new Cache_Exception('Memcache driver does not support tags');
    }

    /**
     * Delete cache item by key
     */
    public function delete($keys)
    {
        foreach ($keys as $key) {
            if (! $this->backend->delete($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete cache items by tag
     */
    public function delete_tag($tags)
    {
        throw new Cache_Exception('Memcache driver does not support tags');
    }

    /**
     * Empty the cache
     */
    public function delete_all()
    {
        return $this->backend->flush();
    }
} // End Cache Memcache Driver
