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

use Ampache\Config\AmpConfig;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Browse;
use Ampache\Module\Util\Ui;

$threshold      = AmpConfig::get('stats_threshold');
$limit          = AmpConfig::get('popular_threshold', 10);
$catalog_filter = AmpConfig::get('catalog_filter');
$user_id        = ($catalog_filter)
    ? Core::get_global('user')->id
    : null;

require_once Ui::find_template('show_mashup_browse_form.inc.php');
Ui::show_box_top(T_('Trending'));
$object_ids = Stats::get_top($object_type, $limit, $threshold, 0, $user_id);
$browse     = new Browse();
$browse->set_type($object_type);
$browse->set_show_header(false);
$browse->set_grid_view(false, false);
$browse->set_mashup(true);
$browse->show_objects($object_ids);
Ui::show_box_bottom();
 ?>
<a href="<?php echo AmpConfig::get('web_path') ?>/stats.php?action=newest#browse_content_<?php echo $object_type ?>"><?php Ui::show_box_top(T_('Newest')) ?></a>
<?php
$object_ids = Stats::get_newest($object_type, $limit, 0, 0, $user_id);
$browse     = new Browse();
$browse->set_type($object_type);
$browse->set_show_header(false);
$browse->set_grid_view(false, false);
$browse->set_mashup(true);
$browse->show_objects($object_ids);
Ui::show_box_bottom(); ?>
<a href="<?php echo AmpConfig::get('web_path') ?>/stats.php?action=popular"><?php Ui::show_box_top(T_('Popular')) ?></a>
<?php
$object_ids = array_slice(Stats::get_top($object_type, $limit, $threshold, 0, $user_id), 0, 100);
shuffle($object_ids);
$object_ids = array_slice($object_ids, 0, $limit);
$browse     = new Browse();
$browse->set_type($object_type);
$browse->set_show_header(false);
$browse->set_grid_view(false, false);
$browse->set_mashup(true);
$browse->show_objects($object_ids);
Ui::show_box_bottom();
