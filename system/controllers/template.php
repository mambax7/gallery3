<?php defined('SYSPATH') || die('No direct access allowed.');

/**
 * Allows a template to be automatically loaded and displayed. Display can be
 * dynamically turned off in the controller methods, and the template file
 * can be overloaded.
 *
 * To use it, declare your controller to extend this class:
 * `class Your_Controller extends Template_Controller`
 *
 * $Id: template.php 4729 2009-12-29 20:35:19Z isaiah $
 *
 * @package        Kohana
 * @author         Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license        http://kohanaphp.com/license
 */
abstract class Template_Controller extends Controller
{

    // Template view name
    public $template = 'template';

    // Default to do auto-rendering
    public $auto_render = true;

    /**
     * Template loading and setup routine.
     */
    public function __construct()
    {
        parent::__construct();

        // Load the template
        $this->template = new View($this->template);

        if (true == $this->auto_render) {
            // Render the template immediately after the controller method
            Event::add('system.post_controller', [$this, '_render']);
        }
    }

    /**
     * Render the loaded template.
     */
    public function _render()
    {
        if (true == $this->auto_render) {
            // Render the template when the class is destroyed
            $this->template->render(true);
        }
    }
} // End Template_Controller
