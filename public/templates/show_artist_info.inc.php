<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Module\Util\Ui;

/** @var Artist $artist */
/** @var array $biography */

?>

<div class="item_info">
    <?php if ($artist instanceof Artist) {
    $thumb = (empty(trim($biography['summary'] ?? ''))) ? 32 : 2;
    Art::display('artist', $artist->id, scrub_out($artist->get_fullname() ?? $artist->name), $thumb);
} ?>
    <div class="item_properties">
        <?php $dcol = array();
        if (array_key_exists('placeformed', $biography) && !empty($biography['placeformed'])) {
            $dcol[] = $biography['placeformed'];
        }
        if (array_key_exists('yearformed', $biography) && (int)$biography['yearformed'] > 0) {
            $dcol[] = $biography['yearformed'];
        }
        if (count($dcol) > 0) {
            echo implode(', ', $dcol);
        } ?>
    </div>
</div>
<div id="item_summary">
    <?php if (array_key_exists('summary', $biography) && !empty($biography['summary'])) { ?>
        <?php echo nl2br(trim($biography['summary'])); ?>
        <?php
    }?>
</div>
<script>
    $(document).ready(function(){
        $("a[rel^='prettyPhoto']").prettyPhoto({social_tools:false});
    });
</script>