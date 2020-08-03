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
 ?>
<?php
$browse = new Browse();
$browse->set_type($object_type);

UI::show_box_top($label->f_name, 'info-box');
if ($label->website) {
    echo "<a href=\"" . scrub_out($label->website) . "\">" . scrub_out($label->website) . "</a><br />";
} ?>
<div class="item_right_info">
    <div class="external_links">
        <a href="http://www.google.com/search?q=%22<?php echo rawurlencode($label->f_name); ?>%22" target="_blank"><?php echo UI::get_icon('google', T_('Search on Google ...')); ?></a>
        <a href="https://www.duckduckgo.com/s?q=%22<?php echo rawurlencode($label->f_name); ?>%22" target="_blank"><?php echo UI::get_icon('duckduckgo', T_('Search on DuckDuckGo ...')); ?></a>
        <a href="http://en.wikipedia.org/wiki/Special:Search?search=%22<?php echo rawurlencode($label->f_name); ?>%22&go=Go" target="_blank"><?php echo UI::get_icon('wikipedia', T_('Search on Wikipedia ...')); ?></a>
        <a href="http://www.last.fm/search?q=%22<?php echo rawurlencode($label->f_name); ?>%22&type=label" target="_blank"><?php echo UI::get_icon('lastfm', T_('Search on Last.fm ...')); ?></a>
    </div>
    <div id="artist_biography">
        <div class="item_info">
            <?php Art::display('label', $label->id, $label->f_name, 2); ?>
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
        <?php if (!AmpConfig::get('use_auth') || Access::check('interface', 25)) { ?>
            <?php if (AmpConfig::get('sociable')) { ?>
            <li>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=label&id=<?php echo $label->id; ?>"><?php echo UI::get_icon('comment', T_('Post Shout')); ?></a>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=label&id=<?php echo $label->id; ?>"><?php echo T_('Post Shout'); ?></a>
            </li>
            <?php
    } ?>
        <?php
} ?>
        <?php if ($label->email) { ?>
        <li>
            <a href="mailto:<?php echo scrub_out($label->email); ?>"><?php echo UI::get_icon('mail', T_('Send E-mail')); ?></a>
            <a href="mailto:<?php echo scrub_out($label->email); ?>"><?php echo T_('Send E-mail'); ?></a>
        </li>
        <?php
    } ?>
        <?php if ($label->can_edit()) { ?>
        <li>
            <a id="<?php echo 'edit_label_' . $label->id ?>" onclick="showEditDialog('label_row', '<?php echo $label->id ?>', '<?php echo 'edit_label_' . $label->id ?>', '<?php echo T_('Label Edit') ?>', '')">
                <?php echo UI::get_icon('edit', T_('Edit')); ?>
            </a>
            <a id="<?php echo 'edit_label_' . $label->id ?>" onclick="showEditDialog('label_row', '<?php echo $label->id ?>', '<?php echo 'edit_label_' . $label->id ?>', '<?php echo T_('Label Edit') ?>', '')">
                <?php echo T_('Edit Label'); ?>
            </a>
        </li>
        <?php
    } ?>
        <?php if (Catalog::can_remove($label)) { ?>
        <li>
            <a id="<?php echo 'delete_label_' . $label->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/labels.php?action=delete&label_id=<?php echo $label->id; ?>">
                <?php echo UI::get_icon('delete', T_('Delete')); ?> <?php echo T_('Delete'); ?>
            </a>
        </li>
        <?php
    } ?>
    </ul>
</div>
<?php UI::show_box_bottom(); ?>
<div class="tabs_wrapper">
    <div id="tabs_container">
        <ul id="tabs">
            <li class="tab_active"><a href="#artists"><?php echo T_('Artists'); ?></a></li>
            <li><a id="songs_link" href="#songs"><?php echo T_('Songs'); ?></a></li>
        </ul>
    </div>
    <div id="tabs_content">
        <div id="artists" class="tab_content" style="display: block;">
<?php
    $browse->show_objects($object_ids, true);
    $browse->set_use_alpha(false, false);
    $browse->store(); ?>
        </div>
<?php
    echo Ajax::observe('songs_link', 'click', Ajax::action('?page=index&action=songs&label=' . $label->id, 'songs')); ?>
        <div id="songs" class="tab_content">
        <?php UI::show_box_top(T_('Songs'), 'info-box'); echo T_('Loading...'); UI::show_box_bottom(); ?>
        </div>
    </div>
</div>
