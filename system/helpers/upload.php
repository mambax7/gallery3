<?php defined('SYSPATH') || die('No direct access allowed.');

/**
 * Upload helper class for working with the global $_FILES
 * array and Validation library.
 *
 * @package        Kohana
 * @author         Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license        http://kohanaphp.com/license
 */
class upload_Core
{

    /**
     * Save an uploaded file to a new location.
     *
     * @param   mixed    name of $_FILE input or array of upload data
     * @param   string   new filename
     * @param   string   new directory
     * @param   integer  chmod mask
     * @return  string   full path to new file
     */
    public static function save($file, $filename = null, $directory = null, $chmod = 0644)
    {
        // Load file data from FILES if not passed as array
        $file = is_array($file) ? $file : $_FILES[$file];

        if (null === $filename) {
            // Use the default filename, with a timestamp pre-pended
            $filename = time() . $file['name'];
        }

        if (true === Kohana::config('upload.remove_spaces')) {
            // Remove spaces from the filename
            $filename = preg_replace('/\s+/', '_', $filename);
        }

        if (null === $directory) {
            // Use the pre-configured upload directory
            $directory = Kohana::config('upload.directory', true);
        }

        // Make sure the directory ends with a slash
        $directory = rtrim($directory, '/') . '/';

        if (!is_dir($directory) && true === Kohana::config('upload.create_directories')) {
            // Create the upload directory
            mkdir($directory, 0777, true);
        }

        if (!is_writable($directory)) {
            throw new Kohana_Exception('The upload destination folder, :dir:, does not appear to be writable.', [':dir:' => $directory]);
        }

        if (is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $filename = $directory . $filename)) {
            if (false !== $chmod) {
                // Set permissions on filename
                chmod($filename, $chmod);
            }

            // Return new file path
            return $filename;
        }

        return false;
    }

    /* Validation Rules */

    /**
     * Tests if input data is valid file type, even if no upload is present.
     *
     * @param   array $_FILES item
     * @return  bool
     */
    public static function valid($file)
    {
        return (is_array($file)
                && isset($file['error'])
                && isset($file['name'])
                && isset($file['type'])
                && isset($file['tmp_name'])
                && isset($file['size']));
    }

    /**
     * Tests if input data has valid upload data.
     *
     * @param   array $_FILES item
     * @return  bool
     */
    public static function required(array $file)
    {
        return (isset($file['tmp_name'])
                && isset($file['error'])
                && is_uploaded_file($file['tmp_name'])
                && UPLOAD_ERR_OK === (int)$file['error']);
    }

    /**
     * Validation rule to test if an uploaded file is allowed by extension.
     *
     * @param   array $_FILES item
     * @param   array    allowed file extensions
     * @return  bool
     */
    public static function type(array $file, array $allowed_types)
    {
        if (UPLOAD_ERR_OK !== (int)$file['error']) {
            return true;
        }

        // Get the default extension of the file
        $extension = strtolower(substr(strrchr($file['name'], '.'), 1));

        // Make sure there is an extension and that the extension is allowed
        return (!empty($extension) && in_array($extension, $allowed_types));
    }

    /**
     * Validation rule to test if an uploaded file is allowed by file size.
     * File sizes are defined as: SB, where S is the size (1, 15, 300, etc) and
     * B is the byte modifier: (B)ytes, (K)ilobytes, (M)egabytes, (G)igabytes.
     * Eg: to limit the size to 1MB or less, you would use "1M".
     *
     * @param   array $_FILES item
     * @param   array    maximum file size
     * @return  bool
     */
    public static function size(array $file, array $size)
    {
        if (UPLOAD_ERR_OK !== (int)$file['error']) {
            return true;
        }

        // Only one size is allowed
        $size = strtoupper($size[0]);

        if (!preg_match('/[0-9]++[BKMG]/', $size)) {
            return false;
        }

        // Make the size into a power of 1024
        switch (substr($size, -1)) {
            case 'G':
                $size = (int)$size * pow(1024, 3);
                break;
            case 'M':
                $size = (int)$size * pow(1024, 2);
                break;
            case 'K':
                $size = (int)$size * pow(1024, 1);
                break;
            default:
                $size = (int)$size;
                break;
        }

        // Test that the file is under or equal to the max size
        return ($file['size'] <= $size);
    }
} // End upload
