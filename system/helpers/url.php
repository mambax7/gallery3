<?php defined('SYSPATH') or die('No direct access allowed.');
/**
 * URL helper class.
 *
 * @package    Kohana
 * @author     Kohana Team
 * @copyright  (c) 2007-2009 Kohana Team
 * @license    http://kohanaphp.com/license
 */
class url_Core
{

    /**
     * Fetches the current URI.
     *
     * @param   boolean  include the query string
     * @param   boolean  include the suffix
     * @return  string
     */
    public static function current($qs = false, $suffix = false)
    {
        $uri = (true === $qs) ? Router::$complete_uri : Router::$current_uri;

        return (true === $suffix) ? $uri . Kohana::config('core.url_suffix') : $uri;
    }

    /**
     * Base URL, with or without the index page.
     *
     * If protocol (and core.site_protocol) and core.site_domain are both empty,
     * then
     *
     * @param   boolean  include the index page
     * @param   boolean  non-default protocol
     * @return  string
     */
    public static function base($index = false, $protocol = false)
    {
        if (false == $protocol) {
            // Use the default configured protocol
            $protocol = Kohana::config('core.site_protocol');
        }

        // Load the site domain
        $site_domain = (string) Kohana::config('core.site_domain', true);

        if (false == $protocol) {
            if ('' === $site_domain or '/' === $site_domain[0]) {
                // Use the configured site domain
                $base_url = $site_domain;
            } else {
                // Guess the protocol to provide full http://domain/path URL
                $base_url = ((empty($_SERVER['HTTPS']) or 'off' === $_SERVER['HTTPS']) ? 'http' : 'https') . '://' . $site_domain;
            }
        } else {
            if ('' === $site_domain or '/' === $site_domain[0]) {
                // Guess the server name if the domain starts with slash
                $port = $_SERVER['SERVER_PORT'];
                $port = (((80 == $port) && ('http' == $protocol)) || ((443 == $port) && ('https' == $protocol)) || !$port) ? '' : ":$port";
                $base_url = $protocol.'://'.($_SERVER['SERVER_NAME']?($_SERVER['SERVER_NAME'].$port):$_SERVER['HTTP_HOST']).$site_domain;
            } else {
                // Use the configured site domain
                $base_url = $protocol.'://'.$site_domain;
            }
        }

        if (true === $index and $index = Kohana::config('core.index_page')) {
            // Append the index page
            $base_url = $base_url.$index;
        }

        // Force a slash on the end of the URL
        return rtrim($base_url, '/').'/';
    }

    /**
     * Fetches an absolute site URL based on a URI segment.
     *
     * @param   string  site URI to convert
     * @param   string  non-default protocol
     * @return  string
     */
    public static function site($uri = '', $protocol = false)
    {
        if ($path = trim(parse_url($uri, PHP_URL_PATH), '/')) {
            // Add path suffix
            $path .= Kohana::config('core.url_suffix');
        }

        if ($query = parse_url($uri, PHP_URL_QUERY)) {
            // ?query=string
            $query = '?'.$query;
        }

        if ($fragment = parse_url($uri, PHP_URL_FRAGMENT)) {
            // #fragment
            $fragment =  '#'.$fragment;
        }

        // Concat the URL
        return url::base(true, $protocol).$path.$query.$fragment;
    }

    /**
     * Return the URL to a file. Absolute filenames and relative filenames
     * are allowed.
     *
     * @param   string   filename
     * @param   boolean  include the index page
     * @return  string
     */
    public static function file($file, $index = false)
    {
        if (false === strpos($file, '://')) {
            // Add the base URL to the filename
            $file = url::base($index).$file;
        }

        return $file;
    }

    /**
     * Merges an array of arguments with the current URI and query string to
     * overload, instead of replace, the current query string.
     *
     * @param   array   associative array of arguments
     * @return  string
     */
    public static function merge(array $arguments)
    {
        if ($_GET === $arguments) {
            $query = Router::$query_string;
        } elseif ($query = http_build_query(array_merge($_GET, $arguments))) {
            $query = '?'.$query;
        }

        // Return the current URI with the arguments merged into the query string
        return Router::$current_uri.$query;
    }

    /**
     * Convert a phrase to a URL-safe title.
     *
     * @param   string  phrase to convert
     * @param   string  word separator (- or _)
     * @param   boolean  transliterate to ASCII
     * @return  string
     */
    public static function title($title, $separator = '-', $ascii_only = false)
    {
        $separator = ('-' === $separator) ? '-' : '_';

        if (true === $ascii_only) {
            // Replace accented characters by their unaccented equivalents
            $title = text::transliterate_to_ascii($title);

            // Remove all characters that are not the separator, a-z, 0-9, or whitespace
            $title = preg_replace('/[^'.$separator.'a-z0-9\s]+/', '', strtolower($title));
        } else {
            // Remove all characters that are not the separator, letters, numbers, or whitespace
            $title = preg_replace('/[^'.$separator.'\pL\pN\s]+/u', '', mb_strtolower($title));
        }

        // Replace all separator characters and whitespace by a single separator
        $title = preg_replace('/['.$separator.'\s]+/', $separator, $title);

        // Trim separators from the beginning and end
        return trim($title, $separator);
    }

    /**
     * Sends a page redirect header and runs the system.redirect Event.
     *
     * @param  mixed   string site URI or URL to redirect to, or array of strings if method is 300
     * @param  string  HTTP method of redirect
     * @return void
     */
    public static function redirect($uri = '', $method = '302')
    {
        if (Event::has_run('system.send_headers')) {
            return false;
        }

        $codes = [
            'refresh' => 'Refresh',
            '300' => 'Multiple Choices',
            '301' => 'Moved Permanently',
            '302' => 'Found',
            '303' => 'See Other',
            '304' => 'Not Modified',
            '305' => 'Use Proxy',
            '307' => 'Temporary Redirect'
        ];

        // Validate the method and default to 302
        $method = isset($codes[$method]) ? (string) $method : '302';

        if ('300' === $method) {
            $uri = (array) $uri;

            $output = '<ul>';
            foreach ($uri as $link) {
                $output .= '<li>'.html::anchor($link).'</li>';
            }
            $output .= '</ul>';

            // The first URI will be used for the Location header
            $uri = $uri[0];
        } else {
            $output = '<p>'.html::anchor($uri).'</p>';
        }

        // Run the redirect event
        Event::run('system.redirect', $uri);

        if (false === strpos($uri, '://')) {
            // HTTP headers expect absolute URLs
            $uri = url::site($uri, request::protocol());
        }

        if ('refresh' === $method) {
            header('Refresh: 0; url='.$uri);
        } else {
            header('HTTP/1.1 '.$method.' '.$codes[$method]);
            header('Location: '.$uri);
        }

        // We are about to exit, so run the send_headers event
        Event::run('system.send_headers');

        exit('<h1>'.$method.' - '.$codes[$method].'</h1>'.$output);
    }
} // End url
