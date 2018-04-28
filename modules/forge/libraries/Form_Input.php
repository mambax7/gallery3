<?php defined('SYSPATH') || die('No direct script access.');
/**
 * FORGE base input library.
 *
 * $Id: Form_Input.php 3326 2008-08-09 21:24:30Z Shadowhand $
 *
 * @package    Forge
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
class Form_Input_Core
{

    // Input method
    public $method;

    // Element data
    protected $data = [
        'type'    => 'text',
        'class'   => 'textbox',
        'value'   => ''
    ];

    // Protected data keys
    protected $protect = [];

    // Validation rules, matches, and callbacks
    protected $rules = [];
    protected $matches = [];
    protected $callbacks = [];

    // Validation check
    protected $is_valid;

    // Errors
    protected $errors = [];
    protected $error_messages = [];

    /**
     * Sets the input element name.
     */
    public function __construct($name)
    {
        $this->data['name'] = $name;
    }

    /**
     * Sets form attributes, or return rules.
     */
    public function __call($method, $args)
    {
        if ('rules' == $method) {
            if (empty($args)) {
                return $this->rules;
            }

            // Set rules and action
            $rules  = $args[0];
            $action = substr($rules, 0, 1);

            if (in_array($action, ['-', '+', '='])) {
                // Remove the action from the rules
                $rules = substr($rules, 1);
            } else {
                // Default action is append
                $action = '';
            }

            $this->add_rules(explode('|', $rules), $action);
        } elseif ('name' == $method) {
            // Do nothing. The name should stay static once it is set.
        } else {
            $this->data[$method] = $args[0];
        }

        return $this;
    }

    /**
     * Returns form attributes.
     *
     * @param   string  attribute name
     * @return  string
     */
    public function __get($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
    }

    /**
     * Sets a form element that this element must match the value of.
     *
     * @chainable
     * @param   object  another Forge input
     * @return  object
     */
    public function matches($input)
    {
        if (! in_array($input, $this->matches, true)) {
            $this->matches[] = $input;
        }

        return $this;
    }

    /**
     * Sets a callback method as a rule for this input.
     *
     * @chainable
     * @param   callback
     * @return  object
     */
    public function callback($callback)
    {
        if (! in_array($callback, $this->callbacks, true)) {
            $this->callbacks[] = $callback;
        }

        return $this;
    }

    /**
     * Sets or returns the input label.
     *
     * @chainable
     * @param   string   label to set
     * @return  string|object
     */
    public function label($val = null)
    {
        if (null === $val) {
            if (isset($this->data['name']) && isset($this->data['label'])) {
                return form::label($this->data['name'], $this->data['label']);
            }
            return false;
        } else {
            $this->data['label'] = (true === $val) ? utf8::ucwords(inflector::humanize($this->name)) : $val;
            return $this;
        }
    }

    /**
     * Set or return the error message.
     *
     * @chainable
     * @param   string  error message
     * @return  strong|object
     */
    public function message($val = null)
    {
        if (null === $val) {
            if (isset($this->data['message'])) {
                return $this->data['message'];
            }
        } else {
            $this->data['message'] = $val;
            return $this;
        }
    }

    /**
     * Runs validation and returns the element HTML.
     *
     * @return  string
     */
    public function render()
    {
        // Make sure validation runs
        $this->validate();

        return $this->html_element();
    }

    /**
     * Returns the form input HTML.
     *
     * @return  string
     */
    protected function html_element()
    {
        $data = $this->data;

        unset($data['label']);
        unset($data['message']);

        return form::input($data);
    }

    /**
     * Replace, remove, or append rules.
     *
     * @param   array   rules to change
     * @param   string  action to use: replace, remove, append
     */
    protected function add_rules(array $rules, $action)
    {
        if ('=' === $action) {
            // Just replace the rules
            $this->rules = $rules;
            return;
        }

        foreach ($rules as $rule) {
            if ('-' === $action) {
                if (false !== ($key = array_search($rule, $this->rules))) {
                    // Remove the rule
                    unset($this->rules[$key]);
                }
            } else {
                if (! in_array($rule, $this->rules)) {
                    if ('+' == $action) {
                        array_unshift($this->rules, $rule);
                    } else {
                        $this->rules[] = $rule;
                    }
                }
            }
        }
    }

    /**
     * Add an error to the input.
     *
     * @chainable
     * @return object
     */
    public function add_error($key, $val)
    {
        if (! isset($this->errors[$key])) {
            $this->errors[$key] = $val;
        }

        return $this;
    }

    /**
     * Set or return the error messages.
     *
     * @chainable
     * @param   string|array  failed validation function, or an array of messages
     * @param   string        error message
     * @return  object|array
     */
    public function error_messages($func = null, $message = null)
    {
        // Set custom error messages
        if (! empty($func)) {
            if (is_array($func)) {
                // Replace all
                $this->error_messages = $func;
            } else {
                if (empty($message)) {
                    // Single error, replaces all others
                    $this->error_messages = $func;
                } else {
                    // Add custom error
                    $this->error_messages[$func] = $message;
                }
            }
            return $this;
        }

        // Make sure validation runs
        null === $this->is_valid && $this->validate();

        // Return single error
        if (! is_array($this->error_messages) && ! empty($this->errors)) {
            return [$this->error_messages];
        }

        $messages = [];
        foreach ($this->errors as $func => $args) {
            if (is_string($args)) {
                $error = $args;
            } else {
                // Force args to be an array
                $args = is_array($args) ? $args : [];

                // Add the label or name to the beginning of the args
                array_unshift($args, $this->label ? mb_strtolower($this->label) : $this->name);

                if (isset($this->error_messages[$func])) {
                    // Use custom error message
                    $error = vsprintf($this->error_messages[$func], $args);
                } else {
                    // Get the proper i18n entry, very hacky but it works
                    switch ($func) {
                        case 'valid_url':
                        case 'valid_email':
                        case 'valid_ip':
                            // Fetch an i18n error message
                                                        $error = 'validation.'.$func;
                            break;
                        case 'valid_' === substr($func, 0, 6):
                            // Strip 'valid_' from func name
                            $func = ('valid_' === substr($func, 0, 6)) ? substr($func, 6) : $func;
                            // no break
                        case 'alpha':
                        case 'alpha_dash':
                        case 'digit':
                        case 'numeric':
                            // i18n strings have to be inserted into valid_type
                            $args[] = 'validation.'.$func;
                            $error = 'validation.valid_type';
                            break;
                        default:
                            $error = 'validation.'.$func;
                    }
                }
            }

            // Add error to list
            $messages[] = $error;
        }

        return $messages;
    }

    /**
     * Get the global input value.
     *
     * @return  string|bool
     */
    protected function input_value($name = [])
    {
        // Get the Input instance
        $input = Input::instance();

        // Fetch the method for this object
        $method = $this->method;

        return $input->$method($name, null);
    }

    /**
     * Load the value of the input, if form data is present.
     *
     * @return  void
     */
    protected function load_value()
    {
        if (is_bool($this->is_valid)) {
            return;
        }

        if ($name = $this->name) {
            // Load POSTed value, but only for named inputs
            $this->data['value'] = $this->input_value($name);
        }

        if (is_string($this->data['value'])) {
            // Trim string values
            $this->data['value'] = trim($this->data['value']);
        }
    }

    /**
     * Validate this input based on the set rules.
     *
     * @return  bool
     */
    public function validate()
    {
        // Validation has already run
        if (is_bool($this->is_valid)) {
            return $this->is_valid;
        }

        // No data to validate
        if (false == $this->input_value()) {
            return $this->is_valid = false;
        }

        // Load the submitted value
        $this->load_value();

        // No rules to validate
        if (0 == count($this->rules) && 0 == count($this->matches) && 0 == count($this->callbacks)) {
            return $this->is_valid = true;
        }

        if (! empty($this->rules)) {
            foreach ($this->rules as $rule) {
                if (false !== ($offset = strpos($rule, '['))) {
                    // Get the args
                    $args = preg_split('/, ?/', trim(substr($rule, $offset), '[]'));

                    // Remove the args from the rule
                    $rule = substr($rule, 0, $offset);
                }

                if ('valid_' === substr($rule, 0, 6) && method_exists('valid', substr($rule, 6))) {
                    $func = substr($rule, 6);

                    if ($this->value && ! valid::$func($this->value)) {
                        $this->errors[$rule] = true;
                    }
                } elseif (method_exists($this, 'rule_'.$rule)) {
                    // The rule function is always prefixed with rule_
                    $rule = 'rule_'.$rule;

                    if (isset($args)) {
                        // Manually call up to 2 args for speed
                        switch (count($args)) {
                            case 1:
                                $this->$rule($args[0]);
                            break;
                            case 2:
                                $this->$rule($args[0], $args[1]);
                            break;
                            default:
                                call_user_func_array([$this, $rule], $args);
                            break;
                        }
                    } else {
                        // Just call the rule
                        $this->$rule();
                    }

                    // Prevent args from being re-used
                    unset($args);
                } else {
                    throw new Kohana_Exception('validation.invalid_rule', $rule);
                }

                // Stop when an error occurs
                if (! empty($this->errors)) {
                    break;
                }
            }
        }

        if (! empty($this->matches)) {
            foreach ($this->matches as $input) {
                if ($this->value != $input->value) {
                    // Field does not match
                    $this->errors['matches'] = [$input->label ? mb_strtolower($input->label) : $input->name];
                    break;
                }
            }
        }

        if (! empty($this->callbacks)) {
            foreach ($this->callbacks as $callback) {
                call_user_func($callback, $this);

                // Stop when an error occurs
                if (! empty($this->errors)) {
                    break;
                }
            }
        }

        // If there are errors, validation failed
        return $this->is_valid = empty($this->errors);
    }

    /**
     * Validate required.
     */
    protected function rule_required()
    {
        if ('' === $this->value or null === $this->value) {
            $this->errors['required'] = true;
        }
    }

    /**
     * Validate length.
     */
    protected function rule_length($min, $max = null)
    {
        // Get the length, return if zero
        if (0 === ($length = mb_strlen($this->value))) {
            return;
        }

        if (null == $max) {
            if ($length != $min) {
                $this->errors['exact_length'] = [$min];
            }
        } else {
            if ($length < $min) {
                $this->errors['min_length'] = [$min];
            } elseif ($length > $max) {
                $this->errors['max_length'] = [$max];
            }
        }
    }
} // End Form Input
