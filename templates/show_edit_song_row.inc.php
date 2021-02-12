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

use Lib\Metadata\Model\Metadata;

?>
<div>
    <form method="post" id="edit_song_<?php echo $libitem->id; ?>" class="edit_dialog_content">
        <table class="tabledata">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Title') ?></td>
                <td><input type="text" name="title" value="<?php echo scrub_out($libitem->title); ?>" autofocus /></td>
            </tr>
            <?php
                if (Access::check('interface', 75)) { ?>
                <tr>
                    <td class="edit_dialog_content_header"><?php echo T_('Artist') ?></td>
                    <td>
                        <?php show_artist_select('artist', $libitem->artist, true, $libitem->id); ?>
                        <div id="artist_select_song_<?php echo $libitem->id ?>">
                            <?php echo Ajax::observe('artist_select_' . $libitem->id, 'change', 'check_inline_song_edit("artist", ' . $libitem->id . ')'); ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="edit_dialog_content_header"><?php echo T_('Album') ?></td>
                    <td>
                        <?php show_album_select('album', $libitem->album, true, $libitem->id); ?>
                        <div id="album_select_song_<?php echo $libitem->id ?>">
                            <?php echo Ajax::observe('album_select_' . $libitem->id, 'change', 'check_inline_song_edit("album", ' . $libitem->id . ')'); ?>
                        </div>
                    </td>
                </tr>
                <?php
                } ?>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Track') ?></td>
                <td><input type="text" name="track" value="<?php echo scrub_out($libitem->track); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('MusicBrainz ID') ?></td>
                <td>
                    <?php
                        if (Access::check('interface', 50)) { ?>
                            <input type="text" name="mbid" value="<?php echo $libitem->mbid; ?>" />
                        <?php
                        } else {
                            echo $libitem->mbid;
                        } ?>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Composer') ?></td>
                <td><input type="text" name="composer" value="<?php echo scrub_out($libitem->composer); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Comment') ?></td>
                <td><input type="text" name="comment" value="<?php echo scrub_out($libitem->comment); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Label') ?></td>
                <td><input type="text" name="label" value="<?php echo scrub_out($libitem->label); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Year') ?></td>
                <td><input type="text" name="year" value="<?php echo scrub_out($libitem->year); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Tags') ?></td>
                <td><input type="text" name="edit_tags" id="edit_tags" value="<?php echo Tag::get_display($libitem->tags); ?>" /></td>
            </tr>
            <?php
                if (AmpConfig::get('licensing')) { ?>
                    <tr>
                        <td class="edit_dialog_content_header"><?php echo T_('Music License') ?></td>
                        <td>
                            <?php show_license_select('license', $libitem->license, $libitem->id); ?>
                            <div id="album_select_license_<?php echo $libitem->license ?>">
                                <?php echo Ajax::observe('license_select_' . $libitem->license, 'change', 'check_inline_song_edit("license", ' . $libitem->id . ')'); ?>
                            </div>
                        </td>
                    </tr>
                    <?php
                } ?>
        </table>
        <?php if (Song::isCustomMetadataEnabled()): ?>
            <button class="metadataAccordionButton"><?php echo T_('More Metadata') ?></button>
                    <div class="metadataAccordion">
                    <table class="tabledata">
                        <?php
                        $dismetas = $libitem->getDisabledMetadataFields();
                        foreach ($libitem->getMetadata() as $metadata) {
                            /* @var Metadata $metadata */
                            $field = $metadata->getField();
                            if ($field->isPublic() && !in_array($field->getName(), $dismetas)) {
                                echo '<tr>'
                                . '<td class="edit_dialog_content_header">' . $field->getFormattedName() . '</td>'
                                . '<td><input type="text" name="metadata[' . $metadata->getId() . ']" value="' . $metadata->getData() . '"/></td>'
                                . '</tr>';
                            }
                        } ?>
                    </table>
                </div>
            <?php endif; ?>
        <input type="hidden" name="id" value="<?php echo $libitem->id; ?>" />
        <input type="hidden" name="type" value="song_row" />
    </form>

    <script>
        $('.metadataAccordionButton').button().click(function() {
            $('.metadataAccordion').toggle();
         //   $(this).hide();
              $(this).text($(this).text() == 'More Metadata' ? 'Less Metadata' : 'More Metadata');
            return false;
        });
    </script>
</div>
