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
if (installer::already_installed()) {
    $content = render('success.html.php');
} else {
    switch (@$_GET['step']) {
        default:
        case 'welcome':
            $errors = installer::check_environment();
            if ($errors) {
                $content = render('environment_errors.html.php', ['errors' => $errors]);
            } else {
                $content = render('get_db_info.html.php');
            }
            break;

        case 'save_db_info':
            $config = [
                'host'     => $_POST['dbhost'],
                'user'     => $_POST['dbuser'],
                'password' => $_POST['dbpass'],
                'dbname'   => $_POST['dbname'],
                'prefix'   => $_POST['prefix'],
                'type'     => function_exists('mysqli_set_charset') ? 'mysqli' : 'mysql',
            ];
            list($config['host'], $config['port']) = explode(':', $config['host'] . ':');
            foreach ($config as $k => $v) {
                if ('password' == $k) {
                    $config[$k] = str_replace(["'", "\\"], ["\\'", "\\\\"], $v);
                } else {
                    $config[$k] = strtr($v, "'`\\", '___');
                }
            }

            if (!installer::connect($config)) {
                $content = render('invalid_db_info.html.php');
            } elseif (!installer::verify_mysql_version($config)) {
                $content = render('invalid_db_version.html.php');
            } elseif (!installer::select_db($config)) {
                $content = render('missing_db.html.php');
            } elseif (is_string($count = installer::db_empty($config)) || !$count) {
                if (is_string($count)) {
                    $content = oops($count);
                } else {
                    $content = render('db_not_empty.html.php');
                }
            } elseif (!installer::unpack_var()) {
                $content = oops('Unable to create files inside the <code>var</code> directory');
            } elseif (!installer::unpack_sql($config)) {
                $content = oops('Failed to create tables in your database:' . mysql_error());
            } elseif (!installer::create_database_config($config)) {
                $content = oops("Couldn't create var/database.php");
            } else {
                try {
                    list($user, $password) = installer::create_admin($config);
                    installer::create_admin_session($config);
                    $content = render('success.html.php', ['user' => $user, 'password' => $password]);

                    installer::create_private_key($config);
                } catch (Exception $e) {
                    $content = oops($e->getMessage());
                }
            }
            break;
    }
}

include('views/install.html.php');

function render($view, $args = [])
{
    ob_start();
    extract($args);
    include(DOCROOT . 'installer/views/' . $view);
    return ob_get_clean();
}

function oops($error)
{
    return render('oops.html.php', ['error' => $error]);
}
