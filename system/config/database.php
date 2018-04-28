<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * Database connection settings, defined as arrays, or "groups". If no group
 * name is used when loading the database library, the group named "default"
 * will be used.
 *
 * Each group can be connected to independently, and multiple groups can be
 * connected at once. For more information about connecting to a database group
 * see the [Database] library.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */

/**
 * Group Options:
 *
 *  - benchmark     - Enable or disable database benchmarking
 *  - persistent    - Enable or disable a persistent connection
 *  - connection    - Array of connection specific parameters; alternatively,
 *                  you can use a DSN though it is not as fast and certain
 *                  characters could create problems (like an '@' character
 *                  in a password):
 *                  'connection'    => 'mysql://dbuser:secret@localhost/kohana'
 *  - character_set - Database character set
 *  - table_prefix  - Database table prefix
 *  - object        - Enable or disable object results
 *  - cache         - Enable or disable query caching
 *  - escape        - Enable automatic query builder escaping
 */
$config['default'] = array(
    'benchmark'     => false,
    'persistent'    => false,
    'connection'    => array(
        'type'     => 'mysql',
        'user'     => 'dbuser',
        'pass'     => 'p@ssw0rd',
        'host'     => 'localhost',
        'port'     => false,
        'socket'   => false,
        'database' => 'kohana',
        'params'   => null,
    ),
    'character_set' => 'utf8',
    'table_prefix'  => '',
    'object'        => true,
    'cache'         => false,
    'escape'        => true
);