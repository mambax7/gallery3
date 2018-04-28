<?php defined('SYSPATH') || die('No direct script access.');

/**
 * FORGE checkbox input library.
 *
 * $Id: Form_Checkbox.php 3326 2008-08-09 21:24:30Z Shadowhand $
 *
 * @package        Forge
 * @author         Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license        http://kohanaphp.com/license.html
 */
class Form_Checkbox_Core extends Form_Input
{
    protected $data = [
        'type'    => 'checkbox',
        'class'   => 'checkbox',
        'value'   => '1',
        'checked' => false,
    ];

    protected $protect = ['type'];

    public function __get($key)
    {
        if ('value' == $key) {
            // Return the value if the checkbox is checked
            return $this->data['checked'] ? $this->data['value'] : null;
        }

        return parent::__get($key);
    }

    public function label($val = null)
    {
        if (null === $val) {
            // Do not display labels for checkboxes, labels wrap checkboxes
            return '';
        } else {
            $this->data['label'] = (true === $val) ? utf8::ucwords(inflector::humanize($this->name)) : $val;
            return $this;
        }
    }

    protected function html_element()
    {
        // Import the data
        $data = $this->data;

        if (empty($data['checked'])) {
            // Not checked
            unset($data['checked']);
        } else {
            // Is checked
            $data['checked'] = 'checked';
        }

        if ($label = arr::remove('label', $data)) {
            // There must be one space before the text
            $label = ' ' . ltrim($label);
        }

        return '<label>' . form::input($data) . html::clean($label) . '</label>';
    }

    protected function load_value()
    {
        if (is_bool($this->valid)) {
            return;
        }

        // Makes the box checked if the value from POST is the same as the current value
        $this->data['checked'] = ($this->input_value($this->name) == $this->data['value']);
    }
} // End Form Checkbox
