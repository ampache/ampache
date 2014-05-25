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

/**
 * get_song_files
 *
 * Takes an array of song ids and returns an array of the actual filenames
 *
 * @param    array    $media_ids    Media IDs.
 */
function get_song_files($media_ids)
{
    $media_files = array();

    $total_size = 0;
    foreach ($media_ids as $element) {
        if (is_array($element)) {
            $type = array_shift($element);
            $media = new $type(array_shift($element));
        } else {
            $media = new Song($element);
        }
        if ($media->enabled) {
            $total_size += sprintf("%.2f",($media->size/1048576));
            $media->format();
            $dirname = $media->f_album_full;
            //debug_event('batch.lib.php', 'Songs file {'.$media->file.'}...', '5');
            if (!array_key_exists($dirname, $media_files)) {
                $media_files[$dirname] = array();
            }
            array_push($media_files[$dirname], $media->file);
        }
    }

    return array($media_files, $total_size);
} //get_song_files

/**
 * send_zip
 *
 * takes array of full paths to songs
 * zips them and sends them
 *
 * @param    string    $name    name of the zip file to be created
 * @param    array    $song_files    array of full paths to songs to zip create w/ call to get_song_files
 */
function send_zip($name, $song_files)
{
    // Check if they want to save it to a file, if so then make sure they've
    // got a defined path as well and that it's writable.
    $basedir = '';
    if (AmpConfig::get('file_zip_download') && AmpConfig::get('tmp_dir_path')) {
        // Check writeable
        if (!is_writable(AmpConfig::get('tmp_dir_path'))) {
            $in_memory = '1';
            debug_event('Error','File Zip Path:' . AmpConfig::get('tmp_dir_path') . ' is not writable','1');
        } else {
            $in_memory = '0';
            $basedir = AmpConfig::get('tmp_dir_path');
        }
    } else {
        $in_memory = '1';
    } // if file downloads

    /* Require needed library */
    require_once AmpConfig::get('prefix') . '/modules/archive/archive.lib.php';
    $arc = new zip_file($name . ".zip" );
    $options = array(
        'inmemory'      => $in_memory,  // create archive in memory
        'basedir'       => $basedir,
        'storepaths'    => 0,           // only store file name, not full path
        'level'         => 0,           // no compression
        'comment'       => AmpConfig::get('file_zip_comment'),
        'type'          => "zip"
    );

    $arc->set_options( $options );
    foreach ($song_files as $dir => $files) {
        $arc->add_files($files, $dir);
    }

    if (count($arc->error)) {
        debug_event('archive',"Error: unable to add songs",'3');
        return false;
    } // if failed to add songs

    if (!$arc->create_archive()) {
        debug_event('archive',"Error: unable to create archive",'3');
        return false;
    } // if failed to create archive

    $arc->download_file();

} // send_zip
