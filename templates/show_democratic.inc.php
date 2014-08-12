<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$string = $democratic->is_enabled() ? sprintf(T_('%s Playlist') ,$democratic->name) : T_('Democratic Playlist');
UI::show_box_top($string , 'info-box');
?>
<div id="information_actions">
<ul>
<?php if ($democratic->is_enabled()) { ?>
<li>
    <?php echo T_('Cooldown'); ?>:<?php echo $democratic->f_cooldown; ?>
</li>
<?php } ?>
<?php if (Access::check('interface','75')) { ?>
<li>
    <a href="<?php echo AmpConfig::get('web_path'); ?>/democratic.php?action=manage"><?php echo UI::get_icon('server_lightning', T_('Configure Democratic Playlist')); ?>
    &nbsp;
    <?php echo T_('Configure Democratic Playlist'); ?></a>
</li>
<?php if ($democratic->is_enabled()) { ?>
<li>
    <?php echo Ajax::button('?page=democratic&action=send_playlist&democratic_id=' . $democratic->id,'all', T_('Play'),'play_democratic'); ?>
    <?php echo Ajax::text('?page=democratic&action=send_playlist&democratic_id=' . $democratic->id, T_('Play Democratic Playlist'),'play_democratic_full_text'); ?>
</li>
<li>
    <?php echo Ajax::button('?page=democratic&action=clear_playlist&democratic_id=' . $democratic->id,'delete', T_('Clear Playlist'),'clear_democratic'); ?>
    <?php echo Ajax::text('?page=democratic&action=clear_playlist&democratic_id=' . $democratic->id, T_('Clear Playlist'),'clear_democratic_full_text'); ?>
</li>
<?php } ?>
<?php } ?>
</ul>
</div>
<div style="text-align: right;">
    <script language="javascript" type="text/javascript">
        function reloadPageChanged(obj)
        {
            if (obj.checked) {
                setTimeout(function() {
                    if (obj.checked) {
                        window.location.href = window.location.href<?php echo " + '&dummy=" . time() . "'"; if (!isset($_GET['reloadpage'])) echo " + '&reloadpage=1'"; ?>;
                    }
                }, <?php echo (AmpConfig::get('refresh_limit') * 1000); ?>);
            }
        }
        <?php if (isset($_GET['reloadpage'])) { ?>
        $(document).ready(function() {
            reloadPageChanged(document.getElementById('chkreloadpage'));
        });
        <?php } ?>
    </script>
    <input type="checkbox" id='chkreloadpage' onClick="reloadPageChanged(this);" <?php if (isset($_GET['reloadpage'])) echo "checked"; ?> /> <?php echo T_('Reload this page automatically'); ?>
</div>
<?php UI::show_box_bottom(); ?>
