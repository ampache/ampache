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
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;

/** @var int|string $upload_max */
/** @var string $ajaxfs */

// Upload form from http://tutorialzine.com/2013/05/mini-ajax-file-upload-form/?>
<?php
Ui::show_box_top(T_('Upload'));
$artist   = (int) (Core::get_request('artist'));
$album    = (int) (Core::get_request('album'));
$web_path = (string)AmpConfig::get('web_path', '');
$access50 = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
$user_id  = (!empty(Core::get_global('user'))) ? Core::get_global('user')->id : -1; ?>

<div id="jstreecontainer" role="main">
    <div id="tree"></div>
    <div id="data">
        <div class="treecontent code" style="display:none;"><textarea id="code" readonly="readonly"></textarea></div>
        <div class="treecontent folder" style="display:none;"></div>
        <div class="treecontent image" style="display:none; position:relative;"><img src="" alt="" style="display:block; position:absolute; left:50%; top:50%; padding:0; max-height:90%; max-width:90%;" /></div>
        <div class="treecontent default" style="text-align:center;"><?php echo T_('Target folder'); ?></div>
    </div>
</div>
<script src="<?php echo $web_path; ?>/lib/components/jstree/jstree.min.js"></script>
<script>
$(window).resize(function () {
    var h = Math.max($(window).height() - 0, 420);
    $('#container, #data, #tree, #data .treecontent').height(100).filter('.default').css('lineHeight', '100px');
}).resize();
$(function () {
    $('#tree')
        .jstree({
            'core' : {
                'data' : {
                    'url' : '<?php echo $ajaxfs; ?>?operation=get_node',
                    'data' : function (node) {
                        return { 'id' : node.id };
                    }
                },
                'check_callback' : function(o, n, p, i, m) {
                    if (m && m.dnd && m.pos !== 'i') { return false; }
                    if (o === "move_node" || o === "copy_node") {
                        if (this.get_node(n).parent === this.get_node(p).id) { return false; }
                    }
                    return true;
                },
                'themes' : {
                    'responsive' : false,
                    'variant' : 'small',
                    'stripes' : true
                }
            },
            'sort' : function(a, b) {
                return this.get_type(a) === this.get_type(b) ? (this.get_text(a) > this.get_text(b) ? 1 : -1) : (this.get_type(a) >= this.get_type(b) ? 1 : -1);
            },
            'contextmenu' : {
                'items' : function(node) {
                    var tmp = $.jstree.defaults.contextmenu.items();
                    delete tmp.create.action;
                    tmp.create.label = "New";
                    tmp.create.submenu = {
                        "create_folder" : {
                            "separator_after"    : true,
                            "label"                : "Folder",
                            "action"            : function (data) {
                                var inst = $.jstree.reference(data.reference),
                                    obj = inst.get_node(data.reference);
                                inst.create_node(obj, { type : "default", text : "New folder" }, "last", function (new_node) {
                                    setTimeout(function () { inst.edit(new_node); },0);
                                });
                            }
                        }
                    };
                    if (this.get_type(node) === "file") {
                        delete tmp.create;
                    }
                    return tmp;
                }
            },
            'types' : {
                'default' : { 'icon' : 'folder' },
                'file' : { 'valid_children' : [], 'icon' : 'file' }
            },
            'plugins' : ['state', 'dnd', 'sort', 'types', 'contextmenu', 'unique']
        })
        .on('delete_node.jstree', function (e, data) {
            $.get('<?php echo $ajaxfs; ?>?operation=delete_node', { 'id' : data.node.id })
                .fail(function () {
                    data.instance.refresh();
                });
        })
        .on('create_node.jstree', function (e, data) {
            $.get('<?php echo $ajaxfs; ?>?operation=create_node', { 'type' : data.node.type, 'id' : data.node.parent, 'text' : data.node.text })
                .done(function (d) {
                    data.instance.set_id(data.node, d.id);
                })
                .fail(function () {
                    data.instance.refresh();
                });
        })
        .on('rename_node.jstree', function (e, data) {
            $.get('<?php echo $ajaxfs; ?>?operation=rename_node', { 'id' : data.node.id, 'text' : data.text })
                .done(function (d) {
                    data.instance.set_id(data.node, d.id);
                })
                .fail(function () {
                    data.instance.refresh();
                });
        })
        .on('move_node.jstree', function (e, data) {
            $.get('<?php echo $ajaxfs; ?>?operation=move_node', { 'id' : data.node.id, 'parent' : data.parent })
                .done(function (d) {
                    //data.instance.load_node(data.parent);
                    data.instance.refresh();
                })
                .fail(function () {
                    data.instance.refresh();
                });
        })
        .on('copy_node.jstree', function (e, data) {
            $.get('<?php echo $ajaxfs; ?>?operation=copy_node', { 'id' : data.original.id, 'parent' : data.parent })
                .done(function (d) {
                    //data.instance.load_node(data.parent);
                    data.instance.refresh();
                })
                .fail(function () {
                    data.instance.refresh();
                });
        })
        .on('changed.jstree', function (e, data) {
            if (data && data.selected && data.selected.length) {
                $.get('<?php echo $ajaxfs; ?>?operation=get_content&id=' + data.selected.join(':'), function (d) {
                    if (d && typeof d.type !== 'undefined') {
                        $('#folder').val(d.content);
                    }
                });
            } else {
                $('#data .treecontent').hide();
                $('#data .default').html('<?php echo T_('Target folder'); ?>').show();
            }
        });
});
</script>

<table class="tabledata">
    <tr>
    <h5><?php echo T_('Leave the artist and album fields blank to read file tags'); ?></h5>
    </tr>
</table>
<table class="tabledata">
<tr>
    <td class="edit_dialog_content_header"><?php echo T_('Artist'); ?></td>
    <td class="upload_select">
        <?php show_artist_select('artist', $artist, true, 1, true); ?>
        <div id="artist_select_1">
            <?php echo Ajax::observe('artist_select_1', 'change', 'check_inline_song_edit("artist", 1)'); ?>
        </div>
    </td>
</tr>
<tr>
    <td class="edit_dialog_content_header"><?php echo T_('Album'); ?></td>
    <td class="upload_select">
        <?php show_album_select('album_id', $album, true, 1, true); ?>
        <div id="album_select_1">
            <?php echo Ajax::observe('album_select_1', 'change', 'check_inline_song_edit("album", 1)'); ?>
        </div>
    </td>
</tr>
<?php if (AmpConfig::get('licensing')) { ?>
<tr>
    <td class="edit_dialog_content_header"><?php echo T_('Music License'); ?></td>
    <td class="upload_select">
        <?php show_license_select('license'); ?>
        <div id="album_select_license">
            <?php echo Ajax::observe('license_select', 'change', 'check_inline_song_edit("license", "0")'); ?>
        </div>
    </td>
</tr>
<?php } ?>
</table>
<table class="tabledata">
<tr>
    <td>
        <?php echo T_('Files'); ?>
        <?php
        if ($upload_max > 0) {
            echo " (< " . Ui::format_bytes($upload_max) . ")";
        } ?>
        <br /><br />
        <?php echo T_('Allowed file type'); ?>:<br />
        <?php echo str_replace("|", ", ", AmpConfig::get('catalog_file_pattern')); ?>
    </td>
</tr>
</table>

<input type="file" class="filepond" name="upl" multiple>
<input type="hidden" id="folder" name="folder" value="" />

<script>
    FilePond?.create(document.querySelector('input[type="file"].filepond'), {
        server: {
            url: "<?php echo $web_path; ?>/upload.php",
            process: {
                method: 'POST', // Set the HTTP method to POST
                ondata: (formData) => {
                    formData.append('upload_action', 'upload');
                    formData.append('folder', document.querySelector("#folder").value);
                    return formData;
                }
            },
        },
    });

    var filepondStrings = {
        labelIdle: "<?php echo T_('Drag & Drop your files or <span class=\"filepond--label-action\"> Browse </span>'); ?>",
        labelInvalidField: "<?php echo T_('Field contains invalid files'); ?>",
        labelFileWaitingForSize: "<?php echo T_('Waiting for size'); ?>",
        labelFileSizeNotAvailable: "<?php echo T_('Size not available'); ?>",
        labelFileLoading: "<?php echo T_('Loading'); ?>",
        labelFileLoadError: "<?php echo T_('Error during load'); ?>",
        labelFileProcessing: "<?php echo T_('Uploading'); ?>",
        labelFileProcessingComplete: "<?php echo T_('Upload complete'); ?>",
        labelFileProcessingAborted: "<?php echo T_('Upload cancelled'); ?>",
        labelFileProcessingError: "<?php echo T_('Error during upload'); ?>",
        labelFileProcessingRevertError: "<?php echo T_('Error during revert'); ?>",
        labelFileRemoveError: "<?php echo T_('Error during remove'); ?>",
        labelTapToCancel: "<?php echo T_('tap to cancel'); ?>",
        labelTapToRetry: "<?php echo T_('tap to retry'); ?>",
        labelTapToUndo: "<?php echo T_('tap to undo'); ?>",
        labelButtonRemoveItem: "<?php echo T_('Remove'); ?>",
        labelButtonAbortItemLoad: "<?php echo T_('Abort'); ?>",
        labelButtonRetryItemLoad: "<?php echo T_('Retry'); ?>",
        labelButtonAbortItemProcessing: "<?php echo T_('Cancel'); ?>",
        labelButtonUndoItemProcessing: "<?php echo T_('Undo'); ?>",
        labelButtonRetryItemProcessing: "<?php echo T_('Retry'); ?>",
        labelButtonProcessItem: "<?php echo T_('Upload'); ?>",
        labelMaxFileSizeExceeded: "<?php echo T_('File is too large'); ?>",
        labelMaxFileSize: "<?php echo T_('Maximum file size is {filesize}'); ?>",
        labelMaxTotalFileSizeExceeded: "<?php echo T_('Maximum total size exceeded'); ?>",
        labelMaxTotalFileSize: "<?php echo T_('Maximum total file size is {filesize}'); ?>",
        labelFileTypeNotAllowed: "<?php echo T_('File of invalid type'); ?>",
        fileValidateTypeLabelExpectedTypes: "<?php echo T_('Expects {allButLastType} or {lastType}'); ?>",
        imageValidateSizeLabelFormatError: "<?php echo T_('Image type not supported'); ?>",
        imageValidateSizeLabelImageSizeTooSmall: "<?php echo T_('Image is too small'); ?>",
        imageValidateSizeLabelImageSizeTooBig: "<?php echo T_('Image is too big'); ?>",
        imageValidateSizeLabelExpectedMinSize: "<?php echo T_('Minimum size is {minWidth} × {minHeight}'); ?>",
        imageValidateSizeLabelExpectedMaxSize: "<?php echo T_('Maximum size is {maxWidth} × {maxHeight}'); ?>",
        imageValidateSizeLabelImageResolutionTooLow: "<?php echo T_('Resolution is too low'); ?>",
        imageValidateSizeLabelImageResolutionTooHigh: "<?php echo T_('Resolution is too high'); ?>",
        imageValidateSizeLabelExpectedMinResolution: "<?php echo T_('Minimum resolution is {minResolution}'); ?>",
        imageValidateSizeLabelExpectedMaxResolution: "<?php echo T_('Maximum resolution is {maxResolution}'); ?>"
    }

    FilePond?.setOptions(filepondStrings);
</script>

<?php Ui::show_box_bottom(); ?>
