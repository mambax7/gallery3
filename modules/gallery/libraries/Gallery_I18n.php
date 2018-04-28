<?php defined('SYSPATH') or die('No direct script access.');
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

/**
 * Translates a localizable message.
 * @param $message String The message to be translated. E.g. "Hello world"
 * @param $options array (optional) Options array for key value pairs which are used
 *        for pluralization and interpolation. Special key: "locale" to override the
 *        currently configured locale.
 * @return String The translated message string.
 */
function t($message, $options= [])
{
    return Gallery_I18n::instance()->translate($message, $options);
}

/**
 * Translates a localizable message with plural forms.
 * @param $singular String The message to be translated. E.g. "There is one album."
 * @param $plural String The plural message to be translated. E.g.
 *        "There are %count albums."
 * @param $count Number The number which is inserted for the %count placeholder and
 *        which is used to select the proper plural form ($singular or $plural).
 * @param $options array (optional) Options array for key value pairs which are used
 *        for pluralization and interpolation. Special key: "locale" to override the
 *        currently configured locale.
 * @return String The translated message string.
 */
function t2($singular, $plural, $count, $options= [])
{
    return Gallery_I18n::instance()->translate(
        ['one' => $singular, 'other' => $plural],
        array_merge($options, ['count' => $count])
  );
}

class Gallery_I18n_Core
{
    private static $_instance;
    private $_config = [];
    private $_call_log = [];
    private $_cache = [];

    private function __construct($config)
    {
        $this->_config = $config;
        $this->locale($config['default_locale']);
    }

    public static function instance($config=null)
    {
        if (null == self::$_instance || isset($config)) {
            $config = isset($config) ? $config : Kohana::config('locale');
            if (empty($config['default_locale'])) {
                $config['default_locale'] = module::get_var('gallery', 'default_locale');
            }
            self::$_instance = new Gallery_I18n_Core($config);
        }

        return self::$_instance;
    }

    public function locale($locale=null)
    {
        if ($locale) {
            $this->_config['default_locale'] = $locale;
            $php_locale = setlocale(LC_ALL, 0);
            list($php_locale, $unused) = explode('.', $php_locale . '.');
            if ($php_locale != $locale) {
                // Attempt to set PHP's locale as well (for number formatting, collation, etc.)
                $locale_prefs = [$locale];
                // Try appending some character set names; some systems (like FreeBSD) need this.
                // Some systems require a format with hyphen (eg. Gentoo) and others without (eg. FreeBSD).
                $charsets = ['utf8', 'UTF-8', 'UTF8', 'ISO8859-1', 'ISO-8859-1'];
                if ('en' != substr($locale, 0, 2)) {
                    $charsets = array_merge($charsets, [
              'EUC', 'Big5', 'euc', 'ISO8859-2', 'ISO8859-5', 'ISO8859-7',
              'ISO8859-9', 'ISO-8859-2', 'ISO-8859-5', 'ISO-8859-7', 'ISO-8859-9'
                    ]);
                }
                foreach ($charsets as $charset) {
                    $locale_prefs[] = $locale . '.' . $charset;
                }
                $locale_prefs[] = 'en_US';
                $php_locale = setlocale(LC_ALL, $locale_prefs);
            }
            if (is_string($php_locale) && 'tr' == substr($php_locale, 0, 2)) {
                // Make PHP 5 work with Turkish (the localization results are mixed though).
                // Hack for http://bugs.php.net/18556
                setlocale(LC_CTYPE, 'C');
            }
        }
        return $this->_config['default_locale'];
    }

    public function is_rtl($locale=null)
    {
        $is_rtl = !empty($this->_config['force_rtl']);
        if (empty($is_rtl)) {
            $locale or $locale = $this->locale();
            list($language, $territory) = explode('_', $locale . '_');
            $is_rtl = in_array($language, ['he', 'fa', 'ar']);
        }
        return $is_rtl;
    }

    /**
     * Translates a localizable message.
     *
     * Security:
     * The returned string is safe for use in HTML (it contains a safe subset of HTML and
     * interpolation parameters are converted to HTML entities).
     * For use in JavaScript, please call ->for_js() on it.
     *
     * @param $message String|array The message to be translated. E.g. "Hello world"
     *                 or array("one" => "One album", "other" => "%count albums")
     * @param $options array (optional) Options array for key value pairs which are used
     *        for pluralization and interpolation. Special keys are "count" and "locale",
     *        the latter to override the currently configured locale.
     * @return String The translated message string.
     */
    public function translate($message, $options= [])
    {
        $locale = empty($options['locale']) ? $this->_config['default_locale'] : $options['locale'];
        $count = isset($options['count']) ? $options['count'] : null;
        $values = $options;
        unset($values['locale']);
        $this->log($message, $options);

        $entry = $this->lookup($locale, $message);

        if (null === $entry) {
            // Default to the root locale.
            $entry = $message;
            $locale = $this->_config['root_locale'];
        }

        $entry = $this->pluralize($locale, $entry, $count);

        $entry = $this->interpolate($locale, $entry, $values);

        return SafeString::of_safe_html($entry);
    }

    private function lookup($locale, $message)
    {
        if (!isset($this->_cache[$locale])) {
            $this->_cache[$locale] = self::load_translations($locale);
        }

        $key = self::get_message_key($message);

        if (isset($this->_cache[$locale][$key])) {
            return $this->_cache[$locale][$key];
        } else {
            return null;
        }
    }

    private static function load_translations($locale)
    {
        $cache_key = 'translation|' . $locale;
        $cache = Cache::instance();
        $translations = $cache->get($cache_key);
        if (!isset($translations) || !is_array($translations)) {
            $translations = [];
            foreach (db::build()
               ->select('key', 'translation')
               ->from('incoming_translations')
               ->where('locale', '=', $locale)
               ->execute() as $row) {
                $translations[$row->key] = unserialize($row->translation);
            }

            // Override incoming with outgoing...
            foreach (db::build()
               ->select('key', 'translation')
               ->from('outgoing_translations')
               ->where('locale', '=', $locale)
               ->execute() as $row) {
                $translations[$row->key] = unserialize($row->translation);
            }

            $cache->set($cache_key, $translations, ['translation'], 0);
        }
        return $translations;
    }

    public function has_translation($message, $options=null)
    {
        $locale = empty($options['locale']) ? $this->_config['default_locale'] : $options['locale'];

        $entry = $this->lookup($locale, $message);

        if (null === $entry) {
            return false;
        } elseif (!is_array($message)) {
            return '' !== $entry;
        } else {
            if (!is_array($entry) || empty($entry)) {
                return false;
            }
            // It would be better to verify that all the locale's plural forms have a non-empty
            // translation, but this is fine for now.
            foreach ($entry as $value) {
                if ('' === $value) {
                    return false;
                }
            }
            return true;
        }
    }

    public static function get_message_key($message)
    {
        $as_string = is_array($message) ? implode('|', $message) : $message;
        return md5($as_string);
    }

    public static function is_plural_message($message)
    {
        return is_array($message);
    }

    private function interpolate($locale, $string, $key_values)
    {
        // TODO: Handle locale specific number formatting.

        // Replace x_y before replacing x.
        krsort($key_values, SORT_STRING);

        $keys = [];
        $values = [];
        foreach ($key_values as $key => $value) {
            $keys[] = "%$key";
            $values[] = new SafeString($value);
        }
        return str_replace($keys, $values, $string);
    }

    private function pluralize($locale, $entry, $count)
    {
        if (!is_array($entry)) {
            return $entry;
        }

        $plural_key = self::get_plural_key($locale, $count);
        if (!isset($entry[$plural_key])) {
            // Fallback to the default plural form.
            $plural_key = 'other';
        }

        if (isset($entry[$plural_key])) {
            return $entry[$plural_key];
        } else {
            // Fallback to just any plural form.
            list($plural_key, $string) = each($entry);
            return $string;
        }
    }

    private function log($message, $options)
    {
        $key = self::get_message_key($message);
        isset($this->_call_log[$key]) or $this->_call_log[$key] = [$message, $options];
    }

    public function call_log()
    {
        return $this->_call_log;
    }

    public static function clear_cache($locale=null)
    {
        $cache = Cache::instance();
        if ($locale) {
            $cache->delete('translation|' . $locale);
        } else {
            $cache->delete_tag('translation');
        }
    }

    private static function get_plural_key($locale, $count)
    {
        $parts = explode('_', $locale);
        $language = $parts[0];

        // Data from CLDR 1.6 (http://unicode.org/cldr/data/common/supplemental/plurals.xml).
        // Docs: http://www.unicode.org/cldr/data/charts/supplemental/language_plural_rules.html
        switch ($language) {
      case 'az':
      case 'fa':
      case 'hu':
      case 'ja':
      case 'ko':
      case 'my':
      case 'to':
      case 'tr':
      case 'vi':
      case 'yo':
      case 'zh':
      case 'bo':
      case 'dz':
      case 'id':
      case 'jv':
      case 'ka':
      case 'km':
      case 'kn':
      case 'ms':
      case 'th':
        return 'other';

      case 'ar':
        if (0 == $count) {
            return 'zero';
        } elseif (1 == $count) {
            return 'one';
        } elseif (2 == $count) {
            return 'two';
        } elseif (is_int($count) && ($i = $count % 100) >= 3 && $i <= 10) {
            return 'few';
        } elseif (is_int($count) && ($i = $count % 100) >= 11 && $i <= 99) {
            return 'many';
        } else {
            return 'other';
        }

        // no break
      case 'pt':
      case 'am':
      case 'bh':
      case 'fil':
      case 'tl':
      case 'guw':
      case 'hi':
      case 'ln':
      case 'mg':
      case 'nso':
      case 'ti':
      case 'wa':
        if (0 == $count || 1 == $count) {
            return 'one';
        } else {
            return 'other';
        }

        // no break
      case 'fr':
        if ($count >= 0 and $count < 2) {
            return 'one';
        } else {
            return 'other';
        }

        // no break
      case 'lv':
        if (0 == $count) {
            return 'zero';
        } elseif (1 == $count % 10 && 11 != $count % 100) {
            return 'one';
        } else {
            return 'other';
        }

        // no break
      case 'ga':
      case 'se':
      case 'sma':
      case 'smi':
      case 'smj':
      case 'smn':
      case 'sms':
        if (1 == $count) {
            return 'one';
        } elseif (2 == $count) {
            return 'two';
        } else {
            return 'other';
        }

        // no break
      case 'ro':
      case 'mo':
        if (1 == $count) {
            return 'one';
        } elseif (is_int($count) && 0 == $count && ($i = $count % 100) >= 1 && $i <= 19) {
            return 'few';
        } else {
            return 'other';
        }

        // no break
      case 'lt':
        if (is_int($count) && 1 == $count % 10 && 11 != $count % 100) {
            return 'one';
        } elseif (is_int($count) && ($i = $count % 10) >= 2 && $i <= 9 && ($i = $count % 100) < 11 && $i > 19) {
            return 'few';
        } else {
            return 'other';
        }

        // no break
      case 'hr':
      case 'ru':
      case 'sr':
      case 'uk':
      case 'be':
      case 'bs':
      case 'sh':
        if (is_int($count) && 1 == $count % 10 && 11 != $count % 100) {
            return 'one';
        } elseif (is_int($count) && ($i = $count % 10) >= 2 && $i <= 4 && ($i = $count % 100) < 12 && $i > 14) {
            return 'few';
        } elseif (is_int($count) && (0 == $count % 10 || (($i = $count % 10) >= 5 && $i <= 9) || (($i = $count % 100) >= 11 && $i <= 14))) {
            return 'many';
        } else {
            return 'other';
        }

        // no break
      case 'cs':
      case 'sk':
        if (1 == $count) {
            return 'one';
        } elseif (is_int($count) && $count >= 2 && $count <= 4) {
            return 'few';
        } else {
            return 'other';
        }

        // no break
      case 'pl':
        if (1 == $count) {
            return 'one';
        } elseif (is_int($count) && ($i = $count % 10) >= 2 && $i <= 4 &&
                   ($i = $count % 100) < 12 && $i > 14 && ($i = $count % 100) < 22 && $i > 24) {
            return 'few';
        } else {
            return 'other';
        }

        // no break
      case 'sl':
        if (1 == $count % 100) {
            return 'one';
        } elseif (2 == $count % 100) {
            return 'two';
        } elseif (is_int($count) && ($i = $count % 100) >= 3 && $i <= 4) {
            return 'few';
        } else {
            return 'other';
        }

        // no break
      case 'mt':
        if (1 == $count) {
            return 'one';
        } elseif (0 == $count || is_int($count) && ($i = $count % 100) >= 2 && $i <= 10) {
            return 'few';
        } elseif (is_int($count) && ($i = $count % 100) >= 11 && $i <= 19) {
            return 'many';
        } else {
            return 'other';
        }

        // no break
      case 'mk':
        if (1 == $count % 10) {
            return 'one';
        } else {
            return 'other';
        }

        // no break
      case 'cy':
        if (1 == $count) {
            return 'one';
        } elseif (2 == $count) {
            return 'two';
        } elseif (8 == $count || 11 == $count) {
            return 'many';
        } else {
            return 'other';
        }

        // no break
      default: // en, de, etc.
        return 1 == $count ? 'one' : 'other';
    }
    }
}
