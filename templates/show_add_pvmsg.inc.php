<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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

?>
<?php UI::show_box_top(T_('Compose Message'), 'box box_add_pvmsg'); ?>
<form name="label" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/pvmsg.php?action=add_message">
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr>
    <td><?php echo T_('Recipient'); ?></td>
    <td>
        <input type="text" name="to_user" value="<?php echo scrub_out($_REQUEST['to_user']); ?>" id="pvmsg_to_user" />
        <?php Error::display('to_user'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Subject'); ?></td>
    <td>
        <input type="text" name="subject" value="<?php echo scrub_out($_REQUEST['subject']); ?>" />
        <?php Error::display('subject'); ?>
    </td>
</tr>
<tr>
    <td><?php echo T_('Message'); ?></td>
    <td>
        <textarea name="message" cols="64" rows="10"><?php echo scrub_out($_REQUEST['message']); ?></textarea>
        <?php Error::display('message'); ?>
    </td>
</tr>
</table>
<div class="formValidation">
    <?php echo Core::form_register('add_pvmsg'); ?>
    <input class="button" type="submit" value="<?php echo T_('Send'); ?>" />
</div>
</form>
<script type="text/javascript">
$(function() {
    $( "#pvmsg_to_user" ).catcomplete({
        source: function( request, response ) {
            $.getJSON( jsAjaxUrl, {
                page: 'search',
                action: 'search',
                target: 'user',
                search: request.term,
                xoutput: 'json'
            }, response );
        },
        search: function() {
            // custom minLength
            if (this.value.length < 2) {
                return false;
            }
        },
        focus: function() {
            // prevent value inserted on focus
            return false;
        },
        select: function( event, ui ) {
            if (ui.item != null) {
                $(this).val(ui.item.value);
            }
            return false;
        }
    });
});
</script>
<?php UI::show_box_bottom(); ?>
