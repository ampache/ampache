<?php

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
use Ampache\Module\Statistics\Stats;
use Ampache\Repository\Model\Browse;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\User;

/** @var string $object_type */
/** @var User $user */

$threshold      = AmpConfig::get('stats_threshold', 7);
$limit          = (int)AmpConfig::get('popular_threshold', 10);
$web_path       = AmpConfig::get_web_path();

require_once Ui::find_template('show_form_mashup.inc.php');

$object_ids = Stats::get_newest($object_type, $limit, 0, 0, $user);
if (!empty($object_ids)) {
    echo "<a href=\"" . $web_path . "/stats.php?action=newest_" . $object_type . "\">";
    Ui::show_box_top(T_('Newest'));
    echo "</a>";
    $browse = new Browse();
    $browse->set_type($object_type);
    $browse->set_use_filters(false);
    $browse->set_show_header(false);
    $browse->set_grid_view(false, false);
    $browse->set_mashup(true);
    $browse->show_objects($object_ids);
    Ui::show_box_bottom();
}

$object_ids = Stats::get_recent($object_type, $limit);
if (!empty($object_ids)) {
    echo "<a href=\"" . $web_path . "/stats.php?action=recent_" . $object_type . "\">";
    Ui::show_box_top(T_('Recent'));
    echo "</a>";
    $browse = new Browse();
    $browse->set_type($object_type);
    $browse->set_use_filters(false);
    $browse->set_show_header(false);
    $browse->set_grid_view(false, false);
    $browse->set_mashup(true);
    $browse->show_objects($object_ids);
    Ui::show_box_bottom();
}

$object_ids = Stats::get_top($object_type, $limit, $threshold);
if (!empty($object_ids)) {
    Ui::show_box_top(T_('Trending'));
    $browse = new Browse();
    $browse->set_type($object_type);
    $browse->set_use_filters(false);
    $browse->set_show_header(false);
    $browse->set_grid_view(false, false);
    $browse->set_mashup(true);
    $browse->show_objects($object_ids);
    Ui::show_box_bottom();
}

$object_ids = Stats::get_top($object_type, 100, $threshold, 0, $user);
if (!empty($object_ids)) {
    echo "<a href=\"" . $web_path . "/stats.php?action=popular\">";
    Ui::show_box_top(T_('Popular'));
    echo "</a>";
    shuffle($object_ids);
    $object_ids = array_slice($object_ids, 0, $limit);
    $browse     = new Browse();
    $browse->set_type($object_type);
    $browse->set_show_header(false);
    $browse->set_grid_view(false, false);
    $browse->set_mashup(true);
    $browse->show_objects($object_ids);
    Ui::show_box_bottom();
}
