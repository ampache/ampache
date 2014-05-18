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

$confirmation = Core::form_register($form_name);
?>
<?php UI::show_box_top(scrub_out($title), 'box box_confirmation'); ?>
<?php echo $text; ?>
    <br />
    <form method="post" action="<?php echo $path; ?>" style="display:inline;">
        <input type="submit" value="<?php echo T_('Continue'); ?>" />
        <?php echo $confirmation; ?>
    </form>
<?php if ($cancel) { ?>
    <form method="post" action="<?php echo AmpConfig::get('web_path') . '/' . return_referer(); ?>" style="display:inline;">
        <input type="submit" value="<?php echo T_('Cancel'); ?>" />
        <?php echo $confirmation; ?>
    </form>
<?php } ?>
<?php UI::show_box_bottom(); ?>
