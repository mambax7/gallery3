<?php defined('SYSPATH') or die('No direct script access.');
/**
 * FORGE dropdown input library.
 *
 * $Id: Form_Dropdown.php 3326 2008-08-09 21:24:30Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Form_Dropdown_Core extends Form_Input
{
    protected $data = [
        'name'  => '',
        'class' => 'dropdown',
    ];

    protected $protect = ['type'];

    public function __get($key)
    {
        if ('value' == $key) {
            return $this->selected;
        }

        return parent::__get($key);
    }

    public function html_element()
    {
        // Import base data
        $base_data = $this->data;

        unset($base_data['label']);

        // Get the options and default selection
        $options = arr::remove('options', $base_data);
        $selected = arr::remove('selected', $base_data);

        return form::dropdown($base_data, $options, $selected);
    }

    protected function load_value()
    {
        if (is_bool($this->valid)) {
            return;
        }

        $this->data['selected'] = $this->input_value($this->name);
    }

    public function validate()
    {
        // Validation has already run
        if (is_bool($this->is_valid)) {
            return $this->is_valid;
        }

        if (false == $this->input_value()) {
            // No data to validate
            return $this->is_valid = false;
        }

        // Load the submitted value
        $this->load_value();

        if (! array_key_exists($this->value, $this->data['options'])) {
            // Value does not exist in the options
            return $this->is_valid = false;
        }

        return parent::validate();
    }
} // End Form Dropdown
