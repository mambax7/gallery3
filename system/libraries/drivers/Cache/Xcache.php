<?php defined('SYSPATH') || die('No direct access allowed.');

/**
 * XCache-based Cache driver.
 *
 * $Id: Memcache.php 4605 2009-09-14 17:22:21Z kiall $
 *
 * @package        Cache
 * @author         Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license        http://kohanaphp.com/license
 * @TODO           Check if XCache cleans its own keys.
 */
class Cache_Xcache_Driver extends Cache_Driver
{
    protected $config;

    public function __construct($config)
    {
        if (!extension_loaded('xcache')) {
            throw new Cache_Exception('The xcache PHP extension must be loaded to use this driver.');
        }

        $this->config = $config;
    }

    public function set($items, $tags = null, $lifetime = null)
    {
        if (null !== $tags) {
            Kohana_Log::add('debug', __('Cache: XCache driver does not support tags'));
        }

        foreach ($items as $key => $value) {
            if (is_resource($value)) {
                throw new Cache_Exception('Caching of resources is impossible, because resources cannot be serialised.');
            }

            if (!xcache_set($key, $value, $lifetime)) {
                return false;
            }
        }

        return true;
    }

    public function get($keys, $single = false)
    {
        $items = [];

        foreach ($keys as $key) {
            if (xcache_isset($key)) {
                $items[$key] = xcache_get($key);
            } else {
                $items[$key] = null;
            }
        }

        if ($single) {
            return (false === $items || count($items) > 0) ? current($items) : null;
        } else {
            return (false === $items) ? [] : $items;
        }
    }

    /**
     * Get cache items by tag
     */
    public function get_tag($tags)
    {
        Kohana_Log::add('debug', __('Cache: XCache driver does not support tags'));
        return null;
    }

    /**
     * Delete cache item by key
     */
    public function delete($keys)
    {
        foreach ($keys as $key) {
            if (!xcache_unset($key)) {
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
        Kohana_Log::add('debug', __('Cache: XCache driver does not support tags'));
        return null;
    }

    /**
     * Empty the cache
     */
    public function delete_all()
    {
        $this->auth();
        $result = true;

        for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++) {
            if (null !== xcache_clear_cache(XC_TYPE_VAR, $i)) {
                $result = false;
                break;
            }
        }

        // Undo the login
        $this->auth(true);

        return $result;
    }

    private function auth($reverse = false)
    {
        static $backup = [];

        $keys = ['PHP_AUTH_USER', 'PHP_AUTH_PW'];

        foreach ($keys as $key) {
            if ($reverse) {
                if (isset($backup[$key])) {
                    $_SERVER[$key] = $backup[$key];
                    unset($backup[$key]);
                } else {
                    unset($_SERVER[$key]);
                }
            } else {
                $value = getenv($key);

                if (!empty($value)) {
                    $backup[$key] = $value;
                }

                $_SERVER[$key] = $this->config->{$key};
            }
        }
    }
} // End Cache XCache Driver
