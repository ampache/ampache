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
?>
<?php UI::show_box_top(T_('Customize Search'), 'box box_get_albumart'); ?>
<form enctype="multipart/form-data" name="coverart" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/arts.php?action=find_art&object_type=<?php echo $object_type; ?>&object_id=<?php echo $object_id; ?>&burl=<?php echo rawurlencode($burl); ?>&artist_name=<?php echo urlencode($_REQUEST['artist_name']);?>&album_name=<?php echo urlencode($_REQUEST['album_name']); ?>&cover=<?php echo urlencode($_REQUEST['cover']); ?>" style="Display:inline;">
    <table class="tabledata" cellspacing="0" cellpadding="0">
        <?php
        foreach ($keywords as $key => $word) {
            if ($key != 'keyword' && $word['label']) {
        ?>
                <tr>
                    <td>
                        <?php echo $word['label']; ?>&nbsp;
                    </td>
                    <td>
                        <input type="text" id="option_<?php echo $key; ?>" name="option_<?php echo $key; ?>" value="<?php echo scrub_out(unhtmlentities($word['value'])); ?>" />
                    </td>
                </tr>
        <?php
            }
        }
        ?>
        <tr>
            <td>
                <?php echo T_('Direct URL to Image'); ?>
            </td>
            <td>
                <input type="text" id="cover" name="cover" value="" />
            </td>
        </tr>
        <tr>
            <td>
                <?php echo T_('Local Image'); ?>
            </td>
            <td>
                <input type="file" id="file" name="file" value="" />
            </td>
        </tr>
    </table>
    <div class="formValidation">
        <input type="hidden" name="action" value="find_art" />
        <input type="hidden" name="object_type" value="<?php echo $object_type; ?>" />
        <input type="hidden" name="object_id" value="<?php echo $object_id; ?>" />
        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo AmpConfig::get('max_upload_size'); ?>" />
        <input type="button" value="<?php echo T_('Cancel'); ?>" onClick="window.location='<?php echo $burl; ?>';" />
        <input type="submit" value="<?php echo T_('Get Art'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
