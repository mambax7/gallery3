<?php defined("SYSPATH") or die("No direct script access.");
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
class comment_theme_Core
{
    public static function head($theme)
    {
        return $theme->css("comment.css")
      . $theme->script("comment.js");
    }

    public static function admin_head($theme)
    {
        return $theme->css("comment.css");
    }

    public static function photo_bottom($theme)
    {
        $block = new Block;
        $block->css_id = "g-comments";
        $block->title = t("Comments");
        $block->anchor = "comments";

        $view = new View("comments.html");
        $view->comments = ORM::factory("comment")
      ->where("item_id", "=", $theme->item()->id)
      ->where("state", "=", "published")
      ->order_by("created", "ASC")
      ->find_all();

        $block->content = $view;
        return $block;
    }
}
