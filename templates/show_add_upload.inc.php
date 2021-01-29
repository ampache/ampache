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

 // Upload form from http://tutorialzine.com/2013/05/mini-ajax-file-upload-form/?>
<?php
UI::show_box_top(T_('Upload'));
$ajaxfs = AmpConfig::get('ajax_server') . '/fs.ajax.php';
$artist = (int) (Core::get_request('artist'));
$album  = (int) (Core::get_request('album')); ?>
<div id="container" role="main">
    <div id="tree"></div>
    <div id="data">
        <div class="treecontent code" style="display:none;"><textarea id="code" readonly="readonly"></textarea></div>
        <div class="treecontent folder" style="display:none;"></div>
        <div class="treecontent image" style="display:none; position:relative;"><img src="" alt="" style="display:block; position:absolute; left:50%; top:50%; padding:0; max-height:90%; max-width:90%;" /></div>
        <div class="treecontent default" style="text-align:center;"><?php echo T_('Target folder'); ?></div>
    </div>
</div>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/components/jstree/dist/jstree.min.js"></script>
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

<form id="uploadfile" method="post" enctype="multipart/form-data" action="<?php echo AmpConfig::get('web_path'); ?>/upload.php">
<input type="hidden" name="upload_action" value="upload" />
<input type="hidden" id="folder" name="folder" value="" />
<?php
// Display a max file size client side if we know it
if ($upload_max > 0) { ?>
    <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $upload_max; ?>" />
<?php
} ?>
<table class="tabledata">
<?php if (Access::check('interface', 50)) {
    ?>
    <tr>
    <h5><?php echo T_('Leave the artist and album fields blank to read file tags') ?></h5>
    </tr>
</table>
<table class="tabledata">
<tr>
    <td class="edit_dialog_content_header"><?php echo T_('Artist') ?></td>
    <td class="upload_select">
        <?php show_artist_select('artist', $artist, true, 1, Access::check('interface', 50), Access::check('interface', 50) ? null : Core::get_global('user')->id); ?>
        <div id="artist_select_album_1">
            <?php echo Ajax::observe('artist_select_1', 'change', 'check_inline_song_edit("artist", 1)'); ?>
        </div>
    </td>
</tr>
<tr>
    <td class="edit_dialog_content_header"><?php echo T_('Album') ?></td>
    <td class="upload_select">
        <?php show_album_select('album', $album, true, 1, Access::check('interface', 50), Access::check('interface', 50) ? null : Core::get_global('user')->id); ?>
        <div id="album_select_upload_1">
            <?php echo Ajax::observe('album_select_1', 'change', 'check_inline_song_edit("album", 1)'); ?>
        </div>
    </td>
</tr>
<?php
} ?>
<?php if (AmpConfig::get('licensing')) { ?>
<tr>
    <td class="edit_dialog_content_header"><?php echo T_('Music License') ?></td>
    <td class="upload_select">
        <?php show_license_select('license', 0, 0); ?>
        <div id="album_select_license_<?php echo $song->license ?>">
            <?php echo Ajax::observe('license_select', 'change', 'check_inline_song_edit("license", "0")'); ?>
        </div>
    </td>
</tr>
<?php
} ?>
</table>
<table class="tabledata">
<tr>
    <td>
        <?php echo T_('Files'); ?>
        <?php
        if ($upload_max > 0) {
            echo " (< " . UI::format_bytes($upload_max) . ")";
        } ?>
        <br /><br />
        <?php echo T_('Allowed file type'); ?>:<br />
        <?php echo str_replace("|", ", ", AmpConfig::get('catalog_file_pattern')); ?>
    </td>
</tr>
<tr>
    <td>
        <div id="dropfile">
            <?php echo T_('Drop File Here'); ?>
            <a><?php echo T_('Browse'); ?></a>
            <input type="file" name="upl" multiple />

            <ul>
                <!-- The file uploads will be shown here -->
            </ul>
        </div>
    </td>
</tr>
</table>
</form>

<script>
// Helper function that formats the file sizes
function formatFileSize(bytes)
{
    if (typeof bytes !== 'number') {
        return '';
    }

    if (bytes >= 1000000000) {
        return (bytes / 1000000000).toFixed(2) + ' GB';
    }

    if (bytes >= 1000000) {
        return (bytes / 1000000).toFixed(2) + ' MB';
    }

    return (bytes / 1000).toFixed(2) + ' KB';
}

$(function(){

    var ul = $('#uploadfile ul');

    $('#dropfile a').click(function(){
        // Simulate a click on the file input button
        // to show the file browser dialog
        $(this).parent().find('input').click();
    });

    // Initialize the jQuery File Upload plugin
    $('#uploadfile').fileupload({

        // This element will accept file drag/drop uploading
        dropZone: $('#dropfile'),

        // This function is called when a file is added to the queue;
        // either via the browse button, or via drag/drop:
        add: function (e, data) {

            var tpl = $('<li class="working"><input type="text" value="0" data-width="48" data-height="48"'+
                ' data-fgColor="#0788a5" data-readOnly="1" data-bgColor="#3e4043" /><p></p><span></span></li>');

          // Append the file name and file size
            tpl.find('p').text(data.files[0].name)
                         .append('<i>' + formatFileSize(data.files[0].size) + '</i>');

            // Add the HTML to the UL element
            data.context = tpl.appendTo(ul);

            // Initialize the knob plugin
            tpl.find('input').knob();

            // Listen for clicks on the cancel icon
            tpl.find('span').click(function(){

                if (tpl.hasClass('working')) {
                    jqXHR.abort();
                }

                tpl.fadeOut(function(){
                    tpl.remove();
                });

            });

            // Automatically upload the file once it is added to the queue
            var jqXHR = data.submit();
        },

        progress: function(e, data){

            // Calculate the completion percentage of the upload
            var progress = parseInt(data.loaded / data.total * 100, 10);

            // Update the hidden input field and trigger a change
            // so that the jQuery knob plugin knows to update the dial
            data.context.find('input').val(progress).change();

            if (progress == 100) {
                data.context.removeClass('working');
            }
        },

        fail:function(e, data){
            // Something has gone wrong!
            data.context.addClass('error');
        }

    });


    // Prevent the default action when a file is dropped on the window
    $(document).on('drop dragover', function (e) {
        e.preventDefault();
    });
});
</script>
<?php UI::show_box_bottom(); ?>
