<?php
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

namespace Ampache\Module\Util;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * This class handles the Mail
 */
interface MailerInterface
{
    /**
     * Check that the mail feature is enabled
     */
    public function isMailEnabled(): bool;

    /**
     * set_default_sender
     *
     * Does the config magic to figure out the "system" email sender and
     * sets it as the sender.
     */
    public function set_default_sender();

    /**
     * send
     * This actually sends the mail, how amazing
     * @param PHPMailer $phpmailer
     * @return boolean
     * @throws Exception
     */
    public function send($phpmailer = null);

    /**
     * @param $group_name
     * @return boolean
     * @throws Exception
     */
    public function send_to_group($group_name);
}
