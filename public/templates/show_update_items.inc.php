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
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;

/** @var int $object_id */
/** @var int|null $catalog_id */
/** @var string $type */
/** @var string $target_url */

$return_id = Catalog::update_single_item($type, $object_id)['object_id'];
//The target URL has changed so it needs to be updated
if ($object_id != $return_id) {
    $object_id  = $return_id;
    $target_url = AmpConfig::get_web_path() . '/' . $type . 's.php?action=show&' . $type . '=' . $object_id;
} ?>
<br />
<strong><?php echo T_('Update from tags complete'); ?></strong>&nbsp;&nbsp;
<a class="button" href="<?php echo $target_url; ?>"><?php echo T_('Continue'); ?></a>
<br />
<?php
// gather art for this item
$art = new Art($object_id, $type);
if (!$art->has_db_info() && !AmpConfig::get('art_order') == 'db') {
    if ($catalog_id !== null) {
        Catalog::gather_art_item($type, $object_id);
    }
} ?>
