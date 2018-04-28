<?php defined('SYSPATH') || die('No direct script access.');

/**
 * Kohana_Config abstract driver to get and set
 * configuration options.
 *
 * Specific drivers should implement caching and encryption
 * as they deem appropriate.
 *
 * $Id: Config.php 4679 2009-11-10 01:45:52Z isaiah $
 *
 * @package        Kohana_Config
 * @author         Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license        http://kohanaphp.com/license
 * @abstract
 */
abstract class Config_Driver
{

    /**
     * Internal caching
     *
     * @var     Cache
     */
    protected $cache;

    /**
     * The name of the internal cache
     *
     * @var     string
     */
    protected $cache_name = 'Kohana_Config_Cache';

    /**
     * Cache Lifetime
     *
     * @var mixed
     */
    protected $cache_lifetime = false;

    /**
     * The Encryption library
     *
     * @var     Encrypt
     */
    protected $encrypt;

    /**
     * The config loaded
     *
     * @var     array
     */
    protected $config = [];

    /**
     * The changed status of configuration values,
     * current state versus the stored state.
     *
     * @var     bool
     */
    protected $changed = false;

    /**
     * Determines if any config has been loaded yet
     */
    public $loaded = false;

    /**
     * Array driver constructor. Sets up the PHP array
     * driver, including caching and encryption if
     * required
     *
     * @access  public
     */
    public function __construct($config)
    {
        if (false !== ($cache_setting = $config['internal_cache'])) {
            $this->cache_lifetime = $cache_setting;
            // Restore the cached configuration
            $this->config = $this->load_cache();

            if (count($this->config) > 0) {
                $this->loaded = true;
            }

            // Add the save cache method to system.shutshut event
            Event::add('system.shutdown', [$this, 'save_cache']);
        }
    }

    /**
     * Gets a value from config. If required is TRUE
     * then get will throw an exception if value cannot
     * be loaded.
     *
     * @param   string       key  the setting to get
     * @param   bool         slash  remove trailing slashes
     * @param   bool         required  is setting required?
     * @return  mixed
     * @access  public
     */
    public function get($key, $slash = false, $required = false)
    {
        // Get the group name from the key
        $group = explode('.', $key, 2);
        $group = $group[0];

        // Check for existing value and load it dynamically if required
        if (!isset($this->config[$group])) {
            $this->config[$group] = $this->load($group, $required);
        }

        // Get the value of the key string
        $value = Kohana::key_string($this->config, $key);

        if (true === $slash && is_string($value) && '' !== $value) {
            // Force the value to end with "/"
            $value = rtrim($value, '/') . '/';
        }

        if ((true === $required) && (null === $value)) {
            throw new Kohana_Config_Exception('Value not found in config driver');
        }

        $this->loaded = true;
        return $value;
    }

    /**
     * Sets a new value to the configuration
     *
     * @param   string       key
     * @param   mixed        value
     * @return  bool
     * @access  public
     */
    public function set($key, $value)
    {
        // Do this to make sure that the config array is already loaded
        $this->get($key);

        if ('routes.' === substr($key, 0, 7)) {
            // Routes cannot contain sub keys due to possible dots in regex
            $keys = explode('.', $key, 2);
        } else {
            // Convert dot-noted key string to an array
            $keys = explode('.', $key);
        }

        // Used for recursion
        $conf =& $this->config;
        $last = count($keys) - 1;

        foreach ($keys as $i => $k) {
            if ($i === $last) {
                $conf[$k] = $value;
            } else {
                $conf =& $conf[$k];
            }
        }

        if ('core.modules' === substr($key, 0, 12)) {
            // Reprocess the include paths
            Kohana::include_paths(true);
        }

        // Set config to changed
        return $this->changed = true;
    }

    /**
     * Clear the configuration
     *
     * @param   string       group
     * @return  bool
     * @access  public
     */
    public function clear($group)
    {
        // Remove the group from config
        unset($this->config[$group]);

        // Set config to changed
        return $this->changed = true;
    }

    /**
     * Checks whether the setting exists in
     * config
     *
     * @param   string $key
     * @return  bool
     * @access  public
     */
    public function setting_exists($key)
    {
        return null === $this->get($key);
    }

    /**
     * Loads a configuration group based on the setting
     *
     * @param   string       group
     * @param   bool         required
     * @return  array
     * @access  public
     * @abstract
     */
    abstract public function load($group, $required = false);

    /**
     * Loads the cached version of this configuration driver
     *
     * @return  array
     * @access  public
     */
    public function load_cache()
    {
        // Load the cache for this configuration
        $cached_config = Kohana::cache($this->cache_name, $this->cache_lifetime);

        // If the configuration wasn't loaded from the cache
        if (null === $cached_config) {
            $cached_config = [];
        }

        // Return the cached config
        return $cached_config;
    }

    /**
     * Saves a cached version of this configuration driver
     *
     * @return  bool
     * @access  public
     */
    public function save_cache()
    {
        // If this configuration has changed
        if (false !== $this->get('core.internal_cache') && $this->changed) {
            $data = $this->config;

            // Save the cache
            return Kohana::cache_save($this->cache_name, $data, $this->cache_lifetime);
        }

        return true;
    }
} // End Kohana_Config_Driver
