<?php defined('SYSPATH') || die('No direct script access.');

/**
 * FORGE radio input library.
 *
 * $Id: Form_Radio.php 3326 2008-08-09 21:24:30Z Shadowhand $
 *
 * @package        Forge
 * @author         Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license        http://kohanaphp.com/license.html
 */
class Form_Radio_Core extends Form_Checkbox
{
    protected $data = [
        'type'    => 'radio',
        'class'   => 'radio',
        'value'   => '1',
        'checked' => false,
    ];
} // End Form_Radio
