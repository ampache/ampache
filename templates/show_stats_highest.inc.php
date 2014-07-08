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
 *
 */

$sql = Rating::get_highest_sql('song');
$browse = new Browse();
$browse->set_type('song', $sql);
$browse->set_simple_browse(true);
$browse->show_objects();
$browse->store();

$sql = Rating::get_highest_sql('album');
$browse = new Browse();
$browse->set_type('album', $sql);
$browse->set_simple_browse(true);
$browse->show_objects();
$browse->store();

$sql = Rating::get_highest_sql('artist');
$browse = new Browse();
$browse->set_type('artist', $sql);
$browse->set_simple_browse(true);
$browse->show_objects();
$browse->store();

if (AmpConfig::get('allow_video')) {
    $sql = Rating::get_highest_sql('video');
    $browse = new Browse();
    $browse->set_type('video', $sql);
    $browse->set_simple_browse(true);
    $browse->show_objects();
    $browse->store();
}
