<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */ ?>
<?php UI::show_box_top(T_('Customize Search'), 'box box_get_albumart'); ?>
<form enctype="multipart/form-data" name="coverart" method="post" action="<?php echo AmpConfig::get('web_path'); ?>/arts.php?action=find_art&object_type=<?php echo $object_type; ?>&object_id=<?php echo $object_id; ?>&burl=<?php echo base64_encode($burl); ?>&artist_name=<?php echo urlencode(Core::get_request('artist_name'));?>&album_name=<?php echo urlencode(Core::get_request('album_name')); ?>&cover=<?php echo urlencode(Core::get_request('cover')); ?>" style="Display:inline;">
    <table class="tabledata">
        <?php
        foreach ($keywords as $key => $word) {
            if ($key != 'keyword' && $word['label']) { ?>
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
        } ?>
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
                <?php echo T_('Local Image'); ?> (&lt; <?php echo UI::format_bytes(AmpConfig::get('max_upload_size')); ?>)
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
        <?php $cancelurl = ((string) AmpConfig::get('web_path') == '') ? $burl : (AmpConfig::get('web_path') . '/' . $burl); ?>
        <input type="button" value="<?php echo T_('Cancel'); ?>" onClick="NavigateTo('<?php echo $cancelurl; ?>');" />
        <input type="submit" value="<?php echo T_('Get Art'); ?>" />
    </div>
</form>
<?php UI::show_box_bottom(); ?>
