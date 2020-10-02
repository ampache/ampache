<?php

declare(strict_types=0);

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Application\Api;

use Ampache\Module\Authorization\Access;
use Ampache\Application\ApplicationInterface;
use Ampache\Module\Util\AjaxUriRetrieverInterface;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Model\Label;
use Ampache\Model\Tag;
use Ampache\Module\Util\Ui;

final class EditApplication implements ApplicationInterface
{
    private AjaxUriRetrieverInterface $ajaxUriRetriever;

    public function __construct(
        AjaxUriRetrieverInterface $ajaxUriRetriever
    ) {
        $this->ajaxUriRetriever = $ajaxUriRetriever;
    }

    public function run(): void
    {
        $results = '';

        debug_event('edit.server', 'Called for action: {' . Core::get_request('action') . '}', 5);

        // Post first
        $type = $_POST['type'];
        if (empty($type)) {
            $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        }
        $object_id = Core::get_get('id');

        if (empty($type)) {
            $object_type = filter_input(INPUT_GET, 'object_type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        } else {
            $object_type = implode('_', explode('_', $type, -1));
        }

        if (!InterfaceImplementationChecker::is_library_item($object_type) && $object_type != 'share') {
            debug_event('edit.server', 'Type `' . $type . '` is not based on an item library.', 3);

            return;
        }

        $class_name = ObjectTypeToClassNameMapper::map($object_type);
        $libitem    = new $class_name($object_id);
        $libitem->format();

        $level = '50';
        if ($libitem->get_user_owner() == Core::get_global('user')->id) {
            $level = '25';
        }
        if (Core::get_request('action') == 'show_edit_playlist') {
            $level = '25';
        }

        // Make sure they got them rights
        if (!Access::check('interface', (int) $level) || AmpConfig::get('demo_mode')) {
            echo (string) xoutput_from_array(array('rfc3514' => '0x1'));

            return;
        }

        // Switch on the actions
        switch ($_REQUEST['action']) {
            case 'show_edit_object':
                ob_start();
                require Ui::find_template('show_edit_' . $type . '.inc.php');
                $results = ob_get_contents();
                break;
            case 'refresh_updated':
                require Ui::find_template('show_' . $type . '.inc.php');
                $results = ob_get_contents();
                break;
            case 'show_edit_playlist':
                ob_start();
                require Ui::find_template('show_playlists_dialog.inc.php');
                $results = ob_get_contents();
                ob_end_clean();
                break;
            case 'edit_object':
                // Scrub the data, walk recursive through array
                $entities = function (&$data) use (&$entities) {
                    foreach ($data as $key => $value) {
                        $data[$key] = is_array($value) ? $entities($value) : unhtmlentities((string) scrub_in($value));
                    }

                    return $data;
                };
                $entities($_POST);

                $class_name = ObjectTypeToClassNameMapper::map($object_type);
                $libitem    = new $class_name($_POST['id']);
                if ($libitem->get_user_owner() == Core::get_global('user')->id && AmpConfig::get('upload_allow_edit') && !Access::check('interface', 50)) {
                    // TODO: improve this uniqueless check
                    if (filter_has_var(INPUT_POST, 'user')) {
                        unset($_POST['user']);
                    }
                    if (filter_has_var(INPUT_POST, 'artist')) {
                        unset($_POST['artist']);
                    }
                    if (filter_has_var(INPUT_POST, 'artist_name')) {
                        unset($_POST['artist_name']);
                    }
                    if (filter_has_var(INPUT_POST, 'album')) {
                        unset($_POST['album']);
                    }
                    if (filter_has_var(INPUT_POST, 'album_name')) {
                        unset($_POST['album_name']);
                    }
                    if (filter_has_var(INPUT_POST, 'album_artist')) {
                        unset($_POST['album_artist']);
                    }
                    if (filter_has_var(INPUT_POST, 'album_artist_name')) {
                        unset($_POST['album_artist_name']);
                    }
                    if (filter_has_var(INPUT_POST, 'edit_tags')) {
                        $_POST['edit_tags'] = Tag::clean_to_existing($_POST['edit_tags']);
                    }
                    if (filter_has_var(INPUT_POST, 'edit_labels')) {
                        $_POST['edit_labels'] = Label::clean_to_existing($_POST['edit_labels']);
                    }
                    // Check mbid and *_mbid match as it is used as identifier
                    if (filter_has_var(INPUT_POST, 'mbid')) {
                        $_POST['mbid'] = $libitem->mbid;
                    }
                    if (filter_has_var(INPUT_POST, 'mbid_group')) {
                        $_POST['mbid_group'] = $libitem->mbid_group;
                    }
                }

                $libitem->format();
                $new_id     = $libitem->update($_POST);
                $class_name = ObjectTypeToClassNameMapper::map($object_type);
                $libitem    = new $class_name($new_id);
                $libitem->format();

                xoutput_headers();
                $results = array('id' => $new_id);
                echo (string) xoutput_from_array($results);

                return;
            default:
                return;
        } // end switch action

        ob_end_clean();
        echo $results;
    }
}
