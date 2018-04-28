<?php defined('SYSPATH') || die('No direct access allowed.');

/**
 * Log API driver.
 *
 * $Id: Database.php 4679 2009-11-10 01:45:52Z isaiah $
 *
 * @package        Kohana_Log
 * @author         Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license        http://kohanaphp.com/license
 */
class Log_Database_Driver extends Log_Driver
{
    public function save(array $messages)
    {
        $insert = db::build($this->config['group'])->insert($this->config['table'])->columns(['date', 'level', 'message']);

        $run_insert = false;

        foreach ($messages as $message) {
            if ($this->config['log_levels'][$message['type']] <= $this->config['log_threshold']) {
                // Add new message to database
                $insert->values($message);

                // There is data to insert
                $run_insert = true;
            }
        }

        // Update the database
        if ($run_insert) {
            $insert->execute();
        }
    }
}
