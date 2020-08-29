<?php

declare(strict_types=0);

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

namespace Ampache\Application;

use AmpConfig;
use Channel;
use Core;
use Ampache\Module\Util\Ui;

final class ChannelApplication implements ApplicationInterface
{
    public function run(): void
    {
        if (!AmpConfig::get('channel')) {
            Ui::access_denied();

            return;
        }

        Ui::show_header();

        // Switch on the actions
        switch ($_REQUEST['action']) {
            case 'show_create':
                $type = Channel::format_type(Core::get_request('type'));
                if (!empty($type) && !empty($_REQUEST['id'])) {
                    $object = new $type(Core::get_request('id'));
                    if ($object->id) {
                        $object->format();
                        require_once Ui::find_template('show_add_channel.inc.php');
                    }
                }
                Ui::show_footer();

                return;
            case 'create':
                if (AmpConfig::get('demo_mode')) {
                    Ui::access_denied();

                    return;
                }

                if (!Core::form_verify('add_channel', 'post')) {
                    Ui::access_denied();

                    return;
                }

                $created = Channel::create($_REQUEST['name'], $_REQUEST['description'], $_REQUEST['url'], $_REQUEST['type'], $_REQUEST['id'], $_REQUEST['interface'], $_REQUEST['port'], $_REQUEST['admin_password'], isset($_REQUEST['private']) ? 1 : 0, $_REQUEST['max_listeners'], $_REQUEST['random'] ?: 0, $_REQUEST['loop'] ?: 0, $_REQUEST['stream_type'], $_REQUEST['bitrate']);

                if (!$created) {
                    require_once Ui::find_template('show_add_channel.inc.php');
                } else {
                    show_confirmation(T_('No Problem'), T_('The Channel has been created'), AmpConfig::get('web_path') . '/browse.php?action=channel');
                }
                Ui::show_footer();

                return;
            case 'show_delete':
                $object_id = Core::get_request('id');

                $next_url = AmpConfig::get('web_path') . '/channel.php?action=delete&id=' . scrub_out($object_id);
                show_confirmation(T_('Are You Sure?'), T_('This Channel will be deleted'), $next_url, 1, 'delete_channel');
                Ui::show_footer();

                return;
            case 'delete':
                if (AmpConfig::get('demo_mode')) {
                    Ui::access_denied();

                    return;
                }

                $object_id = Core::get_request('id');
                $channel   = new Channel((int) $object_id);
                if ($channel->delete()) {
                    $next_url = AmpConfig::get('web_path') . '/browse.php?action=channel';
                    show_confirmation(T_('No Problem'), T_('The Channel has been deleted'), $next_url);
                }
                Ui::show_footer();

                return;
        } // switch on the action

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();
    }
}
