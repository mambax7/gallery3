<?php defined('SYSPATH') || die('No direct script access.');

/**
 * FORGE (FORm GEneration) library.
 *
 * $Id: Forge.php 3326 2008-08-09 21:24:30Z Shadowhand $
 *
 * @package        Forge
 * @author         Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license        http://kohanaphp.com/license.html
 */
class Forge_Core
{

    // Template variables
    protected $template = [
        'title' => '',
        'class' => '',
        'open'  => '',
        'close' => '',
    ];

    // Form attributes
    protected $attr = [];

    // Form inputs and hidden inputs
    public $inputs = [];
    public $hidden = [];

    // Error message format, only used with custom templates
    public $error_format = '<p class="error">{message}</p>';
    public $newline_char = "\n";

    /**
     * Form constructor. Sets the form action, title, method, and attributes.
     *
     * @return  void
     */
    public function __construct($action = null, $title = '', $method = null, $attr = [])
    {
        // Set form attributes
        $this->attr['action'] = $action;
        $this->attr['method'] = empty($method) ? 'post' : $method;

        // Set template variables
        $this->template['title'] = $title;

        // Empty attributes sets the class to "form"
        empty($attr) && $attr = ['class' => 'form'];

        // String attributes is the class name
        is_string($attr) && $attr = ['class' => $attr];

        // Extend the template with the attributes
        $this->attr += $attr;
    }

    /**
     * Magic __get method. Returns the specified form element.
     *
     * @param   string   unique input name
     * @return  object
     */
    public function __get($key)
    {
        if (isset($this->inputs[$key])) {
            return $this->inputs[$key];
        } elseif (isset($this->hidden[$key])) {
            return $this->hidden[$key];
        }
    }

    /**
     * Magic __call method. Creates a new form element object.
     *
     * @throws  Kohana_Exception
     * @param   string   input type
     * @param   string   input name
     * @return  object
     */
    public function __call($method, $args)
    {
        // Class name
        $input = 'Form_' . ucfirst($method);

        // Create the input
        switch (count($args)) {
            case 1:
                $input = new $input($args[0]);
                break;
            case 2:
                $input = new $input($args[0], $args[1]);
                break;
            default:
                throw new Kohana_Exception('forge.invalid_input', $input);
        }

        if (!($input instanceof Form_Input) && !($input instanceof Forge)) {
            throw new Kohana_Exception('forge.unknown_input', get_class($input));
        }

        $input->method = $this->attr['method'];

        if ($name = $input->name) {
            // Assign by name
            if ('hidden' == $method) {
                $this->hidden[$name] = $input;
            } else {
                $this->inputs[$name] = $input;
            }
        } else {
            // No name, these are unretrievable
            $this->inputs[] = $input;
        }

        return $input;
    }

    /**
     * Set a form attribute. This method is chainable.
     *
     * @param   string|array  attribute name, or an array of attributes
     * @param   string        attribute value
     * @return  object
     */
    public function set_attr($key, $val = null)
    {
        if (is_array($key)) {
            // Merge the new attributes with the old ones
            $this->attr = array_merge($this->attr, $key);
        } else {
            // Set the new attribute
            $this->attr[$key] = $val;
        }

        return $this;
    }

    /**
     * Validates the form by running each inputs validation rules.
     *
     * @return  bool
     */
    public function validate()
    {
        $status = true;

        $inputs = array_merge($this->hidden, $this->inputs);

        foreach ($inputs as $input) {
            if (false == $input->validate()) {
                $status = false;
            }
        }

        return $status;
    }

    /**
     * Returns the form as an array of input names and values.
     *
     * @return  array
     */
    public function as_array()
    {
        $data = [];
        foreach (array_merge($this->hidden, $this->inputs) as $input) {
            if (is_object($input->name)) { // It's a Forge_Group object (hopefully)
                foreach ($input->inputs as $group_input) {
                    if ($name = $group_input->name) {
                        $data[$name] = $group_input->value;
                    }
                }
            } elseif (is_array($input->inputs)) {
                foreach ($input->inputs as $group_input) {
                    if ($name = $group_input->name) {
                        $data[$name] = $group_input->value;
                    }
                }
            } elseif ($name = $input->name) { // It's a normal input
                // Return only named inputs
                $data[$name] = $input->value;
            }
        }
        return $data;
    }

    /**
     * Changes the error message format. Your message formatting must
     * contain a {message} placeholder.
     *
     * @throws  Kohana_Exception
     * @param   string   new message format
     * @return  void
     */
    public function error_format($string = '')
    {
        if (false === strpos((string)$string, '{message}')) {
            throw new Kohana_Exception('validation.error_format');
        }

        $this->error_format = $string;
    }

    /**
     * Creates the form HTML
     *
     * @param   string   form view template name
     * @param   boolean  use a custom view
     * @return  string
     */
    public function render($template = 'forge_template', $custom = false)
    {
        // Load template
        $form = new View($template);

        if ($custom) {
            // Using a custom view

            $data = [];
            foreach (array_merge($this->hidden, $this->inputs) as $input) {
                $data[$input->name] = $input;

                // Groups will never have errors, so skip them
                if ($input instanceof Form_Group) {
                    continue;
                }

                // Compile the error messages for this input
                $messages = '';
                $errors   = $input->error_messages();
                if (is_array($errors) && !empty($errors)) {
                    foreach ($errors as $error) {
                        // Replace the message with the error in the html error string
                        $messages .= str_replace('{message}', $error, $this->error_format) . $this->newline_char;
                    }
                }

                $data[$input->name . '_errors'] = $messages;
            }

            $form->set($data);
        } else {
            // Using a template view

            $form->set($this->template);
            $hidden = [];
            if (!empty($this->hidden)) {
                foreach ($this->hidden as $input) {
                    $hidden['name']  = $input->name;
                    $hidden['value'] = $input->value;
                }
            }

            $form_type = 'open';
            // See if we need a multipart form
            $check_inputs = [$this->inputs];
            while ($check_inputs) {
                foreach (array_shift($check_inputs) as $input) {
                    if ($input instanceof Form_Upload) {
                        $form_type = 'open_multipart';
                    }
                    if ($input instanceof Form_Group) {
                        $check_inputs += [$input->inputs];
                    }
                }
            }

            // Set the form open and close
            $form->open = form::$form_type(arr::remove('action', $this->attr), $this->attr);
            foreach ($this->hidden as $hidden) {
                $form->open .= form::hidden($hidden->name, $hidden->value);
            }
            $form->close = '</form>';

            // Set the inputs
            $form->inputs = $this->inputs;
        }

        return $form;
    }

    /**
     * Returns the form HTML
     */
    public function __toString()
    {
        return (string)$this->render();
    }
} // End Forge
