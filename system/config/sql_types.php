<?php defined('SYSPATH') || die('No direct access allowed.');
/**
 * SQL data types. If there are missing values, please report them
 * at the [issue tracker](http://dev.kohanaphp.com/projects/kohana2/issues)
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */

/**
 * Database sql types
 */
$config = [
    // SQL-92
    'bit'                   => ['type' => 'string', 'exact' => true],
    'bit varying'           => ['type' => 'string'],
    'character'             => ['type' => 'string', 'exact' => true],
    'character varying'     => ['type' => 'string'],
    'date'                  => ['type' => 'string'],
    'decimal'               => ['type' => 'float', 'exact' => true],
    'double precision'      => ['type' => 'float'],
    'float'                 => ['type' => 'float'],
    'integer'               => ['type' => 'int', 'min' => -2147483648, 'max' => 2147483647],
    'interval'              => ['type' => 'string'],
    'national character'    => ['type' => 'string', 'exact' => true],
    'national character varying' => ['type' => 'string'],
    'numeric'               => ['type' => 'float', 'exact' => true],
    'real'                  => ['type' => 'float'],
    'smallint'              => ['type' => 'int', 'min' => -32768, 'max' => 32767],
    'time'                  => ['type' => 'string'],
    'time with time zone'   => ['type' => 'string'],
    'timestamp'             => ['type' => 'string'],
    'timestamp with time zone' => ['type' => 'string'],

    // SQL:1999
    //'array','ref','row'
    'binary large object'   => ['type' => 'string', 'binary' => true],
    'boolean'               => ['type' => 'boolean'],
    'character large object' => ['type' => 'string'],
    'national character large object' => ['type' => 'string'],

    // SQL:2003
    'bigint'    => ['type' => 'int', 'min' => -9223372036854775808, 'max' => 9223372036854775807],

    // SQL:2008
    'binary'            => ['type' => 'string', 'binary' => true, 'exact' => true],
    'binary varying'    => ['type' => 'string', 'binary' => true],

    // MySQL
    'bigint unsigned'   => ['type' => 'int', 'min' => 0, 'max' => 18446744073709551615],
    'decimal unsigned'  => ['type' => 'float', 'exact' => true, 'min' => 0.0],
    'double unsigned'   => ['type' => 'float', 'min' => 0.0],
    'float unsigned'    => ['type' => 'float', 'min' => 0.0],
    'integer unsigned'  => ['type' => 'int', 'min' => 0, 'max' => 4294967295],
    'mediumint'         => ['type' => 'int', 'min' => -8388608, 'max' => 8388607],
    'mediumint unsigned' => ['type' => 'int', 'min' => 0, 'max' => 16777215],
    'real unsigned'     => ['type' => 'float', 'min' => 0.0],
    'smallint unsigned' => ['type' => 'int', 'min' => 0, 'max' => 65535],
    'text'              => ['type' => 'string'],
    'tinyint'           => ['type' => 'int', 'min' => -128, 'max' => 127],
    'tinyint unsigned'  => ['type' => 'int', 'min' => 0, 'max' => 255],
    'year'              => ['type' => 'string'],
];

// SQL-92
$config['char'] = $config['character'];
$config['char varying'] = $config['character varying'];
$config['dec'] = $config['decimal'];
$config['int'] = $config['integer'];
$config['nchar'] = $config['national char'] = $config['national character'];
$config['nchar varying'] = $config['national char varying'] = $config['national character varying'];
$config['varchar'] = $config['character varying'];

// SQL:1999
$config['blob'] = $config['binary large object'];
$config['clob'] = $config['char large object'] = $config['character large object'];
$config['nclob'] = $config['nchar large object'] = $config['national character large object'];
$config['time without time zone'] = $config['time'];
$config['timestamp without time zone'] = $config['timestamp'];

// SQL:2008
$config['varbinary'] = $config['binary varying'];

// MySQL
$config['bool'] = $config['boolean'];
$config['datetime'] = $config['timestamp'];
$config['double'] = $config['double precision'];
$config['double precision unsigned'] = $config['double unsigned'];
$config['enum'] = $config['set'] = $config['character varying'];
$config['fixed'] = $config['decimal'];
$config['fixed unsigned'] = $config['decimal unsigned'];
$config['int unsigned'] = $config['integer unsigned'];
$config['longblob'] = $config['mediumblob'] = $config['tinyblob'] = $config['binary large object'];
$config['longtext'] = $config['mediumtext'] = $config['tinytext'] = $config['text'];
$config['numeric unsigned'] = $config['decimal unsigned'];
$config['nvarchar'] = $config['national varchar'] = $config['national character varying'];
