<?php defined('SYSPATH') || die('No direct access allowed.');

/**
 * Memcache-based Cache driver.
 *
 * $Id: File.php 4605 2009-09-14 17:22:21Z kiall $
 *
 * @package        Cache
 * @author         Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license        http://kohanaphp.com/license
 */
class Cache_File_Driver extends Cache_Driver
{
    protected $config;
    protected $backend;

    public function __construct($config)
    {
        $this->config              = $config;
        $this->config['directory'] = str_replace('\\', '/', realpath($this->config['directory'])) . '/';

        if (!is_dir($this->config['directory']) || !is_writable($this->config['directory'])) {
            throw new Cache_Exception('The configured cache directory, :directory:, is not writable.', [':directory:' => $this->config['directory']]);
        }
    }

    /**
     * Finds an array of files matching the given id or tag.
     *
     * @param  string  cache key or tag
     * @param  bool    search for tags
     * @return array   of filenames matching the id or tag
     */
    public function exists($keys, $tag = false)
    {
        if (true === $keys) {
            // Find all the files
            return glob($this->config['directory'] . '*~*~*');
        } elseif (true === $tag) {
            // Find all the files that have the tag name
            $paths = [];

            foreach ((array)$keys as $tag) {
                $paths = array_merge($paths, glob($this->config['directory'] . '*~*' . $tag . '*~*'));
            }

            // Find all tags matching the given tag
            $files = [];

            foreach ($paths as $path) {
                // Split the files
                $tags = explode('~', basename($path));

                // Find valid tags
                if (3 !== count($tags) || empty($tags[1])) {
                    continue;
                }

                // Split the tags by plus signs, used to separate tags
                $item_tags = explode('+', $tags[1]);

                // Check each supplied tag, and match aginst the tags on each item.
                foreach ($keys as $tag) {
                    if (in_array($tag, $item_tags)) {
                        // Add the file to the array, it has the requested tag
                        $files[] = $path;
                    }
                }
            }

            return $files;
        } else {
            $paths = [];

            foreach ((array)$keys as $key) {
                // Find the file matching the given key
                $paths = array_merge($paths, glob($this->config['directory'] . str_replace(['/', '\\', ' '], '_', $key) . '~*'));
            }

            return $paths;
        }
    }

    public function set($items, $tags = null, $lifetime = null)
    {
        if (0 !== $lifetime) {
            // File driver expects unix timestamp
            $lifetime += time();
        }

        if (null !== $tags && !empty($tags)) {
            // Convert the tags into a string list
            $tags = implode('+', (array)$tags);
        }

        $success = true;

        foreach ($items as $key => $value) {
            if (is_resource($value)) {
                throw new Cache_Exception('Caching of resources is impossible, because resources cannot be serialised.');
            }

            // Remove old cache file
            $this->delete($key);

            if (!(bool)file_put_contents($this->config['directory'] . str_replace(['/', '\\', ' '], '_', $key) . '~' . $tags . '~' . $lifetime, serialize($value))) {
                $success = false;
            }
        }

        return $success;
    }

    public function get($keys, $single = false)
    {
        $items = [];

        if ($files = $this->exists($keys)) {
            // Turn off errors while reading the files
            $ER = error_reporting(0);

            foreach ($files as $file) {
                // Validate that the item has not expired
                if ($this->expired($file)) {
                    continue;
                }

                list($key, $junk) = explode('~', basename($file), 2);

                if (false !== ($data = file_get_contents($file))) {
                    // Unserialize the data
                    $data = unserialize($data);
                } else {
                    $data = null;
                }

                $items[$key] = $data;
            }

            // Turn errors back on
            error_reporting($ER);
        }

        // Reutrn a single item if only one key was requested
        if ($single) {
            return (count($items) > 0) ? current($items) : null;
        } else {
            return $items;
        }
    }

    /**
     * Get cache items by tag
     */
    public function get_tag($tags)
    {
        // An array will always be returned
        $result = [];

        if ($paths = $this->exists($tags, true)) {
            // Find all the files with the given tag
            foreach ($paths as $path) {
                // Get the id from the filename
                list($key, $junk) = explode('~', basename($path), 2);

                if (false !== ($data = $this->get($key, true))) {
                    // Add the result to the array
                    $result[$key] = $data;
                }
            }
        }

        return $result;
    }

    /**
     * Delete cache items by keys or tags
     */
    public function delete($keys, $tag = false)
    {
        $success = true;

        $paths = $this->exists($keys, $tag);

        // Disable all error reporting while deleting
        $ER = error_reporting(0);

        foreach ($paths as $path) {
            // Remove the cache file
            if (!unlink($path)) {
                Kohana_Log::add('error', 'Cache: Unable to delete cache file: ' . $path);
                $success = false;
            }
        }

        // Turn on error reporting again
        error_reporting($ER);

        return $success;
    }

    /**
     * Delete cache items by tag
     */
    public function delete_tag($tags)
    {
        return $this->delete($tags, true);
    }

    /**
     * Empty the cache
     */
    public function delete_all()
    {
        return $this->delete(true);
    }

    /**
     * Check if a cache file has expired by filename.
     *
     * @param  string|array   array of filenames
     * @return bool
     */
    protected function expired($file)
    {
        // Get the expiration time
        $expires = (int)substr($file, strrpos($file, '~') + 1);

        // Expirations of 0 are "never expire"
        return (0 !== $expires && $expires <= time());
    }
} // End Cache Memcache Driver
