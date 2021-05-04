<?php
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

use Ampache\Repository\Model\Shoutbox;
use Ampache\Module\Util\Ui;

?>
<?php Ui::show_box_top(T_('Shoutbox')); ?>
<div id="shoutbox">
<?php
  foreach ($shouts as $shout_id) {
      $shout = new Shoutbox($shout_id); ?>
<div id="shout<?php echo $shout->id; ?>" class="shout">
    <?php echo $shout->get_display(true, true); ?>
</div>
<?php
  } ?>
</div>
<?php Ui::show_box_bottom(); ?>
