<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 * playlist_object
 * Abstracting out functionality needed by both normal and smart playlists
 */
abstract class playlist_object extends database_object
{
    // Database variables
    public $id;
    public $name;
    public $user;
    public $type;

    public $f_type;
    public $f_name;
    public $f_user;

    /**
     * format
     * This takes the current playlist object and gussies it up a little
     * bit so it is presentable to the users
     */
    public function format()
    {
        $this->f_name =  $this->name;
        $this->f_type = ($this->type == 'private') ? UI::get_icon('lock', T_('Private')) : '';

        $client = new User($this->user);

        $this->f_user = $client->fullname;

    } // format

    /**
     * has_access
     * This function returns true or false if the current user
     * has access to this playlist
     */
    public function has_access()
    {
        if (!Access::check('interface','25')) {
            return false;
        }
        if ($this->user == $GLOBALS['user']->id) {
            return true;
        } else {
            return Access::check('interface','100');
        }

    } // has_access


} // end playlist_object
