<?php defined('SYSPATH') || die('No direct script access.');

/**
 * FORGE upload input library.
 *
 * $Id: Form_Upload.php 3326 2008-08-09 21:24:30Z Shadowhand $
 *
 * @package        Forge
 * @author         Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license        http://kohanaphp.com/license.html
 */
class Form_Upload_Core extends Form_Input
{
    protected $data = [
        'class' => 'upload',
        'value' => '',
    ];

    protected $protect = ['type', 'label', 'value'];

    // Upload data
    protected $upload;

    // Upload directory and filename
    protected $directory;
    protected $filename;

    public function __construct($name, $filename = false)
    {
        parent::__construct($name);

        if (!empty($_FILES[$name])) {
            if (empty($_FILES[$name]['tmp_name']) || is_uploaded_file($_FILES[$name]['tmp_name'])) {
                // Cache the upload data in this object
                $this->upload = $_FILES[$name];

                // Hack to allow file-only inputs, where no POST data is present
                $_POST[$name] = $this->upload['name'];

                // Set the filename
                $this->filename = empty($filename) ? false : $filename;
            } else {
                // Attempt to delete the invalid file
                is_writable($_FILES[$name]['tmp_name']) && unlink($_FILES[$name]['tmp_name']);

                // Invalid file upload, possible hacking attempt
                unset($_FILES[$name]);
            }
        }
    }

    /**
     * Sets the upload directory.
     *
     * @param   string   upload directory
     * @return  void
     */
    public function directory($dir = null)
    {
        // Use the global upload directory by default
        empty($dir) && $dir = Kohana::config('upload.directory');

        // Make the path asbolute and normalize it
        $directory = str_replace('\\', '/', realpath($dir)) . '/';

        // Make sure the upload director is valid and writable
        if ('/' === $directory or !is_dir($directory) || !is_writable($directory)) {
            throw new Kohana_Exception('upload.not_writable', $dir);
        }

        $this->directory = $directory;
    }

    public function validate()
    {
        // The upload directory must always be set
        empty($this->directory) && $this->directory();

        // By default, there is no uploaded file
        $filename = '';

        if ($status = parent::validate() && UPLOAD_ERR_OK === $this->upload['error']) {
            // Set the filename to the original name
            $filename = $this->upload['name'];

            if (Kohana::config('upload.remove_spaces')) {
                // Remove spaces, due to global upload configuration
                $filename = preg_replace('/\s+/', '_', $this->data['value']);
            }

            if (file_exists($filepath = $this->directory . $filename)) {
                if (true !== $this->filename or !is_writable($filepath)) {
                    // Prefix the file so that the filename is unique
                    $filepath = $this->directory . 'uploadfile-' . uniqid(time()) . '-' . $this->upload['name'];
                }
            }

            // Move the uploaded file to the upload directory
            move_uploaded_file($this->upload['tmp_name'], $filepath);
        }

        if (!empty($_POST[$this->data['name']])) {
            // Reset the POST value to the new filename
            $this->data['value'] = $_POST[$this->data['name']] = empty($filepath) ? '' : $filepath;
        }

        return $status;
    }

    protected function rule_required()
    {
        if (empty($this->upload) || UPLOAD_ERR_NO_FILE === $this->upload['error']) {
            $this->errors['required'] = true;
        }
    }

    public function rule_allow()
    {
        if (empty($this->upload['tmp_name']) || 0 == count($types = func_get_args())) {
            return;
        }

        if (false === ($mime = file::mime($this->upload['tmp_name']))) {
            // Trust the browser
            $mime = $this->upload['type'];
        }

        // Get rid of the ";charset=binary" that can occasionally occur and is
        // legal via RFC2045
        $mime = preg_replace('/; charset=binary/', '', $mime);

        // Allow nothing by default
        $allow = false;

        foreach ($types as $type) {
            // Load the mime types
            $type = Kohana::config('mimes.' . $type);

            if (is_array($type) && in_array($mime, $type)) {
                // Type is valid
                $allow = true;
                break;
            }
        }

        if (false === $allow) {
            $this->errors['invalid_type'] = true;
        }
    }

    public function rule_size($size)
    {
        // Skip the field if it is empty
        if (empty($this->upload) || UPLOAD_ERR_NO_FILE === $this->upload['error']) {
            return;
        }

        $bytes = (int)$size;

        switch (substr($size, -2)) {
            case 'GB':
                $bytes *= 1024;
            // no break
            case 'MB':
                $bytes *= 1024;
            // no break
            case 'KB':
                $bytes *= 1024;
            // no break
            default:
                break;
        }

        if (empty($this->upload['size']) || $this->upload['size'] > $bytes) {
            $this->errors['max_size'] = [$size];
        }
    }

    protected function html_element()
    {
        return form::upload($this->data);
    }
} // End Form Upload
