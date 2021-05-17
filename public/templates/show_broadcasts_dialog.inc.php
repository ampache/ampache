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

use Ampache\Repository\BroadcastRepositoryInteface;
use Ampache\Repository\Model\Broadcast;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\ModelFactoryInterface;

/** @var ModelFactoryInterface $modelFactory */
/** @var BroadcastRepositoryInteface $broadcastRepository */
global $dic;
$broadcastRepository = $dic->get(BroadcastRepositoryInteface::class);
$modelFactory        = $dic->get(ModelFactoryInterface::class);

?>

<ul>
<?php
    $broadcasts = $broadcastRepository->getByUser(Core::get_global('user')->getId());
    foreach ($broadcasts as $broadcast_id) {
        $broadcast = $modelFactory->createBroadcast((int) $broadcast_id); ?>
    <li>
        <a href="javascript:void(0);" id="rb_append_dbroadcast_<?php echo $broadcast->id; ?>" onclick="handleBroadcastAction('<?php echo $this->ajaxUriRetriever->getAjaxUri() . '?page=player&action=broadcast&broadcast_id=' . $broadcast->id; ?>', 'rb_append_dbroadcast_<?php echo $broadcast->id; ?>');">
            <?php echo $broadcast->getName(); ?>
        </a>
    </li>
<?php
    } ?>
</ul><br />
<a href="javascript:void(0);" id="rb_append_dbroadcast_new" onclick="handleBroadcastAction('<?php echo $this->ajaxUriRetriever->getAjaxUri() . '?page=player&action=broadcast'; ?>', 'rb_append_dbroadcast_new');">
    <?php echo T_('New broadcast'); ?>
</a>
