<?php

declare(strict_types=0);

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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Label;
use Ampache\Module\Util\Ui;

/** @var Label $label */
/** @var array $object_ids */
/** @var string $object_type */
/** @var bool $isLabelEditable */

$browse = new Browse();
$browse->set_type($object_type);
$browse->set_use_filters(false);
// these are usually set so not sure why missing
$limit_threshold = AmpConfig::get('stats_threshold', 7);
$argument        = false;
if (array_key_exists('argument', $_REQUEST)) {
    $argument = (string)scrub_in((string)$_REQUEST['argument']);
}
$f_name   = (string)$label->get_fullname();
$web_path = AmpConfig::get('web_path');
Ui::show_box_top($f_name, 'info-box');
if ($label->website) {
    echo "<a href=\"" . scrub_out($label->website) . "\">" . scrub_out($label->website) . "</a><br />";
} ?>
<div class="item_right_info">
    <div class="external_links">
        <a href="https://www.google.com/search?q=%22<?php echo rawurlencode($f_name); ?>%22" target="_blank"><?php echo Ui::get_icon('google', T_('Search on Google ...')); ?></a>
        <a href="https://www.duckduckgo.com/?q=%22<?php echo rawurlencode($f_name); ?>%22" target="_blank"><?php echo Ui::get_icon('duckduckgo', T_('Search on DuckDuckGo ...')); ?></a>
        <a href="https://en.wikipedia.org/wiki/Special:Search?search=%22<?php echo rawurlencode($f_name); ?>%22&go=Go" target="_blank"><?php echo Ui::get_icon('wikipedia', T_('Search on Wikipedia ...')); ?></a>
        <a href="https://www.last.fm/search?q=%22<?php echo rawurlencode($f_name); ?>%22&type=label" target="_blank"><?php echo Ui::get_icon('lastfm', T_('Search on Last.fm ...')); ?></a>
    </div>
    <div id="artist_biography">
        <div class="item_info">
            <?php Art::display('label', $label->id, $f_name, 2); ?>
            <div class="item_properties">
                <?php echo scrub_out($label->address); ?>
            </div>
        </div>
        <div id="item_summary">
            <?php echo nl2br(scrub_out($label->summary)); ?>
        </div>
    </div>
</div>

<div id="information_actions">
    <h3><?php echo T_('Actions'); ?>:</h3>
    <ul>
        <?php if (!AmpConfig::get('use_auth') || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
            <?php if (AmpConfig::get('sociable')) { ?>
            <li>
                <a href="<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=label&id=<?php echo $label->id; ?>">
                    <?php echo Ui::get_material_symbol('comment', T_('Post Shout')); ?>
                    <?php echo T_('Post Shout'); ?>
                </a>
            </li>
            <?php } ?>
        <?php } ?>
        <?php if ($label->email) { ?>
        <li>
            <a href="mailto:<?php echo scrub_out($label->email); ?>">
                <?php echo Ui::get_material_symbol('mail', T_('Send E-mail')); ?>
                <?php echo T_('Send E-mail'); ?>
            </a>
        </li>
        <?php } ?>
        <?php if ($isLabelEditable) { ?>
        <li>
            <a id="<?php echo 'edit_label_' . $label->id; ?>" onclick="showEditDialog('label_row', '<?php echo $label->id; ?>', '<?php echo 'edit_label_' . $label->id; ?>', '<?php echo addslashes(T_('Label Edit')); ?>', '')">
                <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
                <?php echo T_('Edit Label'); ?>
            </a>
        </li>
        <?php } ?>
        <?php if (Catalog::can_remove($label)) { ?>
        <li>
            <a id="<?php echo 'delete_label_' . $label->id; ?>" href="<?php echo $web_path; ?>/labels.php?action=delete&label_id=<?php echo $label->id; ?>">
                <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
                <?php echo T_('Delete'); ?>
            </a>
        </li>
        <?php } ?>
    </ul>
</div>
<?php Ui::show_box_bottom(); ?>
<div class="tabs_wrapper">
    <div id="tabs_container">
        <ul id="tabs">
            <li class="tab_active"><a href="#artists"><?php echo T_('Artists'); ?></a></li>
            <li><a id="songs_link" href="#songs"><?php echo T_('Songs'); ?></a></li>
        </ul>
    </div>
    <div id="tabs_content">
        <div id="artists" class="tab_content" style="display: block;">
<?php $browse->show_objects($object_ids, true);
$browse->set_use_alpha(false, false);
$browse->store(); ?>
        </div>
<?php echo Ajax::observe('songs_link', 'click', Ajax::action('?page=index&action=songs&label=' . $label->id, 'songs')); ?>
        <div id="songs" class="tab_content">
        <?php Ui::show_box_top(T_('Songs'), 'info-box');
echo T_('Loading...');
Ui::show_box_bottom(); ?>
        </div>
    </div>
</div>
