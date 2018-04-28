<?php defined("SYSPATH") or die("No direct script access.");
/**
 * FORGE group library.
 *
 * $Id: Form_Group.php 3326 2008-08-09 21:24:30Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Form_Group_Core extends Forge
{
    protected $data = array(
        'type'  => 'group',
        'name'  => '',
        'class' => 'group',
        'label' => '',
        'message' => ''
    );

    // Input method
    public $method;

    public function __construct($name = null, $class = 'group')
    {
        $this->data['name'] = $name;
        $this->data['class'] = $class;

        // Set dummy data so we don't get errors
        $this->attr['action'] = '';
        $this->attr['method'] = 'post';
    }

    public function __get($key)
    {
        if ($key == 'type' || $key == 'name' || $key == 'label') {
            return $this->data[$key];
        }
        return parent::__get($key);
    }

    public function __set($key, $val)
    {
        if ($key == 'method') {
            $this->attr['method'] = $val;
        }
        $this->$key = $val;
    }

    public function label($val = null)
    {
        if ($val === null) {
            if ($label = $this->data['label']) {
                return html::purify($this->data['label']);
            }
        } else {
            $this->data['label'] = ($val === true) ? ucwords(inflector::humanize($this->data['name'])) : $val;
            return $this;
        }
    }

    public function message($val = null)
    {
        if ($val === null) {
            return $this->data['message'];
        } else {
            $this->data['message'] = $val;
            return $this;
        }
    }

    public function render($template = 'forge_template', $custom = false)
    {
        // No Sir, we don't want any html today thank you
        return;
    }
} // End Form Group
