<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Config\AmpConfig;
use Ampache\Module\Shout\ShoutObjectLoaderInterface;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Module\Util\Ui;

/** @var ShoutObjectLoaderInterface $shoutObjectLoader */
/** @var list<Shoutbox> $shouts */

$web_path   = AmpConfig::get_web_path('/client');
$admin_path = AmpConfig::get_web_path('/admin');
$t_object   = T_('Object');
$t_user     = T_('User');
$t_sticky   = T_('Sticky');
$t_comment  = T_('Comment');
$t_added    = T_('Date Added');
$t_action   = T_('Action');
// show_shout_row.inc.php
$t_edit     = T_('Edit');
$t_delete   = T_('Delete');
$t_yes      = T_('Yes');
$t_no       = T_('No'); ?>
<table class="tabledata striped-rows">
    <thead>
        <tr class="th-top">
            <th class="cel_object"><?php echo $t_object; ?></th>
            <th class="cel_username"><?php echo $t_user; ?></th>
            <th class="cel_sticky"><?php echo $t_sticky; ?></th>
            <th class="cel_comment"><?php echo $t_comment; ?></th>
            <th class="cel_date"><?php echo $t_added; ?></th>
            <th class="cel_action"><?php echo $t_action; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($shouts as $libitem) {
            $object = $shoutObjectLoader->loadByShout($libitem);
            $client = $libitem->getUser();

            if (
                $client !== null &&
                $object !== null
            ) {
                require Ui::find_template('show_shout_row.inc.php');
            }
            ?>
        <?php
        } ?>
        <?php if ($shouts === []) { ?>
        <tr>
            <td colspan="6" class="error"><?php echo T_('No records found'); ?></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_object"><?php echo $t_object; ?></th>
            <th class="cel_username"><?php echo $t_user; ?></th>
            <th class="cel_sticky"><?php echo $t_sticky; ?></th>
            <th class="cel_comment"><?php echo $t_comment; ?></th>
            <th class="cel_date"><?php echo $t_added; ?></th>
            <th class="cel_action"><?php echo $t_action; ?></th>
        </tr>
    </tfoot>
</table>
