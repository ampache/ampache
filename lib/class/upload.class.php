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

class Upload
{
    /**
     * Constructor
     * This pulls the license information from the database and returns
     * a constructed object
     */
    protected function __construct()
    {
        return false;
    } // Constructor

    public static function process()
    {
        $catalog_id = AmpConfig::get('upload_catalog');
        if ($catalog_id > 0) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog->catalog_type == "local") {
                $allowed = explode('|', AmpConfig::get('catalog_file_pattern'));

                if (isset($_FILES['upl']) && $_FILES['upl']['error'] == 0) {
                    $extension = pathinfo($_FILES['upl']['name'], PATHINFO_EXTENSION);

                    if (!in_array(strtolower($extension), $allowed)) {
                        debug_event('upload', 'File extension `' . $extension . '` not allowed.', '2');
                        echo '{"status":"error"}';
                        return false;
                    }

                    $rootdir = self::get_root($catalog);
                    $targetdir = $rootdir;
                    $folder = $_POST['folder'];
                    if ($folder == '..') {
                        $folder = '';
                    }
                    if (!empty($folder)) {
                        $targetdir .= DIRECTORY_SEPARATOR . $folder;
                    }

                    $targetdir = realpath($targetdir);
                    if (strpos($targetdir, $rootdir) === FALSE) {
                        debug_event('upload', 'Something wrong with final upload path.', '1');
                        echo '{"status":"error"}';
                        return false;
                    }

                    $targetfile = $targetdir . DIRECTORY_SEPARATOR . $_FILES['upl']['name'];
                    if (Core::is_readable($targetfile)) {
                        debug_event('upload', 'File `' . $_FILES['upl']['name'] . '` already exists in target directory.', '1');
                        echo '{"status":"error"}';
                        return false;
                    }

                    if (move_uploaded_file($_FILES['upl']['tmp_name'], $targetfile)) {
                        debug_event('upload', 'File `' . $_FILES['upl']['name'] . '` uploaded.', '5');

                        if (AmpConfig::get('upload_script')) {
                            chdir($targetdir);
                            exec(AmpConfig::get('upload_script'));
                        }

                        $options = array();
                        $options['user_upload'] = $GLOBALS['user']->id;
                        if (isset($_POST['license'])) {
                            $options['license'] = $_POST['license'];
                        }
                        $catalog->add_files($targetdir, $options);

                        echo '{"status":"success"}';
                        return true;
                    } else {
                        debug_event('upload', 'Cannot copy the file to target directory. Please check write access.', '1');
                    }
                }
            } else {
                debug_event('upload', 'The catalog must be local to upload files on it.', '1');
            }
        } else {
            debug_event('upload', 'No catalog target upload configured.', '1');
        }

        echo '{"status":"error"}';
        return false;
    }

    public static function get_root($catalog = null, $username = null)
    {
        if ($catalog == null) {
            $catalog_id = AmpConfig::get('upload_catalog');
            if ($catalog_id > 0) {
                $catalog = Catalog::create_from_id($catalog_id);
            }
        }

        if (is_null($username)) {
            $username = $GLOBALS['user']->username;
        }

        $rootdir = "";
        if ($catalog != null && $catalog->id) {
            $rootdir = realpath($catalog->path);
            if (!empty($rootdir)) {
                if (AmpConfig::get('upload_subdir')) {
                    $rootdir .= DIRECTORY_SEPARATOR . $username;
                    if (!Core::is_readable($rootdir)) {
                        debug_event('upload', 'Target user directory `' . $rootdir . '` doesn\'t exists. Creating it...', '5');
                        mkdir($rootdir);
                    }
                }
            }
        }

        return $rootdir;
    }

} // Upload class
