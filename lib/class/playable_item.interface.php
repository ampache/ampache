<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * playable_item Interface
 *
 * This defines how the playable item file classes should
 * work, this lists all required functions and the expected
 * input
 */
interface playable_item
{
    /**
     * format
     *
     * Creates member variables for output
     */
    public function format();

    /**
     * get_fullname
     *
     * Get the item full name.
     */
    public function get_fullname();

    /**
     * get_parent
     *
     * Get parent. Return parent `object_type`, `object_id` ; null otherwise.
     */
    public function get_parent();

    /**
     * get_childrens
     *
     * Get direct childrens. Return an array of `object_type`, `object_id` childrens.
     */
    public function get_childrens();

    /*
     * get_medias
     *
     * Get all medias from all childrens. Return an array of `object_type`, `object_id` medias.
     */
    public function get_medias($filter_type = null);

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return int[]
     */
    public function get_catalogs();

} // end interface
