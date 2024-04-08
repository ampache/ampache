<?php

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

/** @var int $endTime */
/** @var int $startTime */
/** @var User $user */
/** @var string $year */

$threshold      = AmpConfig::get('stats_threshold', 7);
$limit          = (int)AmpConfig::get('popular_threshold', 10);
$catalog_filter = AmpConfig::get('catalog_filter'); ?>
<h3 class="box-title"><?php echo T_('Ampache Wrapped') . '&nbsp;(' . $year . ')'; ?></h3>
<dl class="media_details">
    <dt><?php echo T_('Songs Played'); ?></dt>
    <dd><?php echo Stats::get_object_data('song_count', $startTime, $endTime, $user); ?></dd>
    <dt><?php echo T_('Minutes Played'); ?></dt>
    <dd><?php echo Stats::get_object_data('song_minutes', $startTime, $endTime, $user); ?></dd>
</dl>
<?php
Ui::show_box_top(T_('Artists'));
$object_ids = Stats::get_top('artist', $limit, $threshold, 0, $user, false, $startTime, $endTime);
$browse     = new Browse();
$browse->set_type('artist');
$browse->set_use_filters(false);
$browse->set_show_header(false);
$browse->set_grid_view(false, false);
$browse->set_mashup(true);
$browse->show_objects($object_ids);
Ui::show_box_bottom();
Ui::show_box_top(T_('Albums'));
$object_ids = Stats::get_top('album', $limit, $threshold, 0, $user, false, $startTime, $endTime);
$browse     = new Browse();
$browse->set_type('album');
$browse->set_use_filters(false);
$browse->set_show_header(false);
$browse->set_grid_view(false, false);
$browse->set_mashup(true);
$browse->show_objects($object_ids);
Ui::show_box_bottom();
Ui::show_box_top(T_('Songs'));
$object_ids = Stats::get_top('song', $limit, $threshold, 0, $user, false, $startTime, $endTime);
$browse     = new Browse();
$browse->set_type('song');
$browse->set_use_filters(false);
$browse->set_show_header(false);
$browse->set_mashup(true);
$browse->show_objects($object_ids);
Ui::show_box_bottom();
Ui::show_box_top(T_('Favorites'));
$object_ids = Userflag::get_latest('song', $user, -1, 0, $startTime, $endTime);
$browse     = new Browse();
$browse->set_type('song');
$browse->set_use_filters(false);
$browse->set_show_header(false);
$browse->set_mashup(true);
$browse->show_objects($object_ids);
Ui::show_box_bottom();
