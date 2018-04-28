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
class item_tags_rest_Core
{
    public static function get($request)
    {
        $item = rest::resolve($request->url);
        $tags = [];
        foreach (tag::item_tags($item) as $tag) {
            $tags[] = rest::url('tag_item', $tag, $item);
        }

        return [
            'url'     => $request->url,
            'members' => $tags
        ];
    }

    public static function post($request)
    {
        $tag = rest::resolve($request->params->entity->tag);
        $item = rest::resolve($request->params->entity->item);
        access::required('view', $item);

        tag::add($item, $tag->name);
        return [
            'url'     => rest::url('tag_item', $tag, $item),
            'members' => [
                rest::url('tag', $tag),
                rest::url('item', $item)
            ]
        ];
    }

    public static function delete($request)
    {
        $item = rest::resolve($request->url);
        access::required('edit', $item);

        // Deleting this collection means removing all tags associated with the item.
        tag::clear_all($item);
    }

    public static function resolve($id)
    {
        $item = ORM::factory('item', $id);
        if (!access::can('view', $item)) {
            throw new Kohana_404_Exception();
        }

        return $item;
    }

    public static function url($item)
    {
        return url::abs_site("rest/item_tags/{$item->id}");
    }
}
