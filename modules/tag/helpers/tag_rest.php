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
class tag_rest_Core
{
    public static function get($request)
    {
        $tag       = rest::resolve($request->url);
        $tag_items = [];
        foreach ($tag->items() as $item) {
            if (access::can('view', $item)) {
                $tag_items[] = rest::url('tag_item', $tag, $item);
            }
        }

        return [
            'url'           => $request->url,
            'entity'        => $tag->as_array(),
            'relationships' => [
                'items' => [
                    'url'     => rest::url('tag_items', $tag),
                    'members' => $tag_items
                ]
            ]
        ];
    }

    public static function put($request)
    {
        // Who can we allow to edit a tag name?  If we allow anybody to do it then any logged in
        // user can rename all your tags to something offensive.  Right now limit renaming to admins.
        if (!identity::active_user()->admin) {
            access::forbidden();
        }
        $tag = rest::resolve($request->url);
        if (isset($request->params->entity->name)) {
            $tag->name = $request->params->entity->name;
            $tag->save();
        }
    }

    public static function delete($request)
    {
        // Restrict deleting tags to admins.  Otherwise, a logged in user can do great harm to an
        // install.
        if (!identity::active_user()->admin) {
            access::forbidden();
        }
        $tag = rest::resolve($request->url);
        $tag->delete();
    }

    public static function relationships($resource_type, $resource)
    {
        switch ($resource_type) {
            case 'item':
                $tags = [];
                foreach (tag::item_tags($resource) as $tag) {
                    $tags[] = rest::url('tag_item', $tag, $resource);
                }
                return [
                    'tags' => [
                        'url'     => rest::url('item_tags', $resource),
                        'members' => $tags
                    ]
                ];
        }
    }

    public static function resolve($id)
    {
        $tag = ORM::factory('tag', $id);
        if (!$tag->loaded()) {
            throw new Kohana_404_Exception();
        }

        return $tag;
    }

    public static function url($tag)
    {
        return url::abs_site("rest/tag/{$tag->id}");
    }
}
