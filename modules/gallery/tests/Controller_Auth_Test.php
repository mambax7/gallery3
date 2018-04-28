<?php defined('SYSPATH') || die('No direct script access.');

/**
 * Gallery - a web based photo album viewer and editor
 * Copyright (C) 2000-2013 Bharat Mediratta
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street - Fifth Floor, Boston, MA  02110-1301, USA.
 */
class Controller_Auth_Test extends Gallery_Unit_Test_Case
{
    public function find_missing_auth_test()
    {
        $found       = [];
        $git_ignores = explode("\n", `git ls-files -o -i --exclude-standard`);
        $controllers = array_diff(glob('*/*/controllers/*.php'), $git_ignores);
        $feeds       = array_diff(glob('*/*/helpers/*_rss.php'), $git_ignores);
        foreach (array_merge($controllers, $feeds) as $controller) {
            if (preg_match('{modules/(gallery_)?unit_test/}', $controller)) {
                continue;
            }

            if (!$controller) {
                // The last entry in each list from git ls-files appears to be an empty line
                continue;
            }

            // List of all tokens without whitespace, simplifying parsing.
            $tokens = [];
            foreach (token_get_all(file_get_contents($controller)) as $token) {
                if (!is_array($token) || T_WHITESPACE != $token[0]) {
                    $tokens[] = $token;
                }
            }

            $is_admin_controller = false;

            $open_braces = 0;
            $function    = null;
            for ($token_number = 0; $token_number < count($tokens); $token_number++) {
                $token = $tokens[$token_number];
                if (self::_token_matches([T_CURLY_OPEN], $tokens, $token_number)) {
                    // Treat this just like a normal open curly brace
                    $token = '{';
                }

                // Count braces.
                // 1 open brace  = in class context.
                // 2 open braces = in function.
                if (!is_array($token)) {
                    if ('}' == $token) {
                        $open_braces--;
                        if (1 == $open_braces && $function) {
                            $found[$controller][] = $function;
                            $function             = null;
                        } elseif (0 == $open_braces) {
                            $is_admin_controller = false;
                        }
                    } elseif ('{' == $token) {
                        $open_braces++;
                    }
                } else {
                    // An array token

                    if (0 == $open_braces && T_EXTENDS == $token[0]) {
                        if (self::_token_matches([T_STRING, 'Admin_Controller'], $tokens, $token_number + 1)) {
                            $is_admin_controller = true;
                        }
                    } elseif (1 == $open_braces && T_FUNCTION == $token[0]) {
                        $line = $token[2];
                        $name = '';
                        // Search backwards to check visibility,
                        // "private function", or "private static function"
                        $previous   = $tokens[$token_number - 1][0];
                        $previous_2 = $tokens[$token_number - 2][0];
                        $is_private = in_array($previous, [T_PRIVATE, T_PROTECTED])
                                      || in_array($previous_2, [T_PRIVATE, T_PROTECTED]);
                        $is_static  = T_STATIC == $previous || T_STATIC == $previous_2;

                        // Search forward to get function name
                        do {
                            $token_number++;
                            if (self::_token_matches([T_STRING], $tokens, $token_number)) {
                                $token = $tokens[$token_number];
                                $name  = $token[1];
                                break;
                            }
                        } while ($token_number < count($tokens));

                        $is_rss_feed = 'feed' == $name && strpos(basename($controller), '_rss.php');

                        if ((!$is_static || $is_rss_feed) && !$is_private) {
                            $function = self::_function($name, $line, $is_admin_controller);
                        }
                    }

                    // Check body of all public functions
                    //
                    // Authorization
                    //   Require: access::required\(
                    // Authentication (CSRF token)
                    //   [When using Input, $this->input, Forge]
                    //   Require: ->validate() or access::verify_csrf\(
                    if ($function && $open_braces >= 2) {
                        if (T_STRING == $token[0]) {
                            if ('access' == $token[1]
                                && self::_token_matches([T_DOUBLE_COLON, '::'], $tokens, $token_number + 1)
                                && self::_token_matches([T_STRING], $tokens, $token_number + 2)
                                && in_array($tokens[$token_number + 2][1], ['forbidden', 'required'])
                                && self::_token_matches('(', $tokens, $token_number + 3)) {
                                $token_number += 3;
                                $function->checks_authorization(true);
                            } elseif ('access' == $token[1]
                                      && self::_token_matches([T_DOUBLE_COLON, '::'], $tokens, $token_number + 1)
                                      && self::_token_matches([T_STRING, 'verify_csrf'], $tokens, $token_number + 2)
                                      && self::_token_matches('(', $tokens, $token_number + 3)) {
                                $token_number += 3;
                                $function->checks_csrf(true);
                            } elseif (in_array($token[1], ['Input', 'Forge'])
                                      && self::_token_matches([T_DOUBLE_COLON, '::'], $tokens, $token_number + 1)) {
                                $token_number++;
                                $function->uses_input(true);
                            }
                        } elseif (T_VARIABLE == $token[0]) {
                            if ('$this' == $token[1]
                                && self::_token_matches([T_OBJECT_OPERATOR], $tokens, $token_number + 1)
                                && self::_token_matches([T_STRING, 'input'], $tokens, $token_number + 2)) {
                                $token_number += 2;
                                $function->uses_input(true);
                            }
                        } elseif (T_OBJECT_OPERATOR == $token[0]) {
                            if (self::_token_matches([T_STRING, 'validate'], $tokens, $token_number + 1)
                                && self::_token_matches('(', $tokens, $token_number + 2)) {
                                $token_number += 2;
                                $function->checks_csrf(true);
                            }
                        }
                    }
                }
            }
        }

        // Generate the report
        $new = TMPPATH . 'controller_auth_data.txt';
        $fd  = fopen($new, 'wb');
        ksort($found);
        foreach ($found as $controller => $functions) {
            $is_admin_controller = true;
            foreach ($functions as $function) {
                $is_admin_controller &= $function->is_admin_controller;
                $flags               = [];
                if ($function->uses_input() && !$function->checks_csrf()) {
                    $flags[] = 'DIRTY_CSRF';
                }
                if (!$function->is_admin_controller && !$function->checks_authorization()) {
                    $flags[] = 'DIRTY_AUTH';
                }

                if (!$flags) {
                    // Don't print CLEAN instances
                    continue;
                }

                fprintf($fd, "%-60s %-20s %s\n", $controller, $function->name, implode('|', $flags));
            }

            if (0 === strpos(basename($controller), 'admin_') && !$is_admin_controller) {
                fprintf($fd, "%-60s %-20s %s\n", $controller, basename($controller), 'NO_ADMIN_CONTROLLER');
            }
        }
        fclose($fd);

        // Compare with the expected report from our golden file.
        $canonical = MODPATH . 'gallery/tests/controller_auth_data.txt';
        exec("diff $canonical $new", $output, $return_value);
        $this->assert_false($return_value, "Controller auth golden file mismatch.  Output:\n" . implode("\n", $output));
    }

    private static function _token_matches($expected_token, &$tokens, $token_number)
    {
        if (!isset($tokens[$token_number])) {
            return false;
        }

        $token = $tokens[$token_number];

        if (is_array($expected_token)) {
            for ($i = 0; $i < count($expected_token); $i++) {
                if ($expected_token[$i] != $token[$i]) {
                    return false;
                }
            }
            return true;
        } else {
            return $expected_token == $token;
        }
    }

    public static function _function($name, $line, $is_admin_controller)
    {
        return new Controller_Auth_Test_Function($name, $line, $is_admin_controller);
    }
}

class Controller_Auth_Test_Function
{
    public $name;
    public $line;
    public $is_admin_controller   = false;
    private $_uses_input           = false;
    private $_checks_authorization = false;
    private $_checks_csrf          = false;

    public function __construct($name, $line, $is_admin_controller)
    {
        $this->name                = $name;
        $this->line                = $line;
        $this->is_admin_controller = $is_admin_controller;
    }

    public function uses_input($val = null)
    {
        if (null !== $val) {
            $this->_uses_input = (bool)$val;
        }
        return $this->_uses_input;
    }

    public function checks_authorization($val = null)
    {
        if (null !== $val) {
            $this->_checks_authorization = (bool)$val;
        }
        return $this->_checks_authorization;
    }

    public function checks_csrf($val = null)
    {
        if (null !== $val) {
            $this->_checks_csrf = $val;
        }
        return $this->_checks_csrf;
    }
}
