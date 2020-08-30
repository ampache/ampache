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

namespace Ampache\Application\Admin;

use Ampache\Module\Authorization\Access;
use Ampache\Application\ApplicationInterface;
use Ampache\Config\AmpConfig;
use Core;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;

final class MailApplication implements ApplicationInterface
{
    public function run(): void
    {
        if (!Access::check('interface', 75)) {
            Ui::access_denied();

            return;
        }

        Ui::show_header();

        // Switch on the actions
        switch ($_REQUEST['action']) {
            case 'send_mail':
                if (AmpConfig::get('demo_mode')) {
                    Ui::access_denied();

                    return;
                }

                // Multi-byte Character Mail
                if (function_exists('mb_language')) {
                    $ini_default_charset = 'default_charset';
                    if (ini_get($ini_default_charset)) {
                        ini_set($ini_default_charset, "UTF-8");
                    }
                    mb_language("uni");
                }

                if (Mailer::is_mail_enabled()) {
                    $mailer = new Mailer();

                    // Set the vars on the object
                    $mailer->subject = $_REQUEST['subject'];
                    $mailer->message = $_REQUEST['message'];

                    if (Core::get_request('from') == 'system') {
                        $mailer->set_default_sender();
                    } else {
                        $mailer->sender      = Core::get_global('user')->email;
                        $mailer->sender_name = Core::get_global('user')->fullname;
                    }

                    if ($mailer->send_to_group($_REQUEST['to'])) {
                        $title  = T_('No Problem');
                        $body   = T_('Your e-mail has been sent');
                    } else {
                        $title     = T_("There Was a Problem");
                        $body      = T_('Your e-mail has not been sent');
                    }
                    $url = AmpConfig::get('web_path') . '/admin/mail.php';
                    show_confirmation($title, $body, $url);
                }

                break;
            default:
                require_once Ui::find_template('show_mail_users.inc.php');
                break;
        } // end switch

        // Show the Footer
        Ui::show_query_stats();
        Ui::show_footer();
    }
}
