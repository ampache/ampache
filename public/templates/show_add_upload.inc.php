<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
$web_path = AmpConfig::get_web_path();
$access50 = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
$user_id  = (!empty(Core::get_global('user'))) ? Core::get_global('user')->id : -1; ?>

<?php echo T_('Target folder'); ?>
<div id="uploadtree" class="wb-alternate"></div>
<div class="upload-actions">
    <button onclick="UUTCreateNode()">
        <?php echo Ui::get_material_symbol('folder'); ?>
        <?php echo T_("New folder");?>
    </button>
    <button onclick="UUTRenameNode()">
        <?php echo Ui::get_material_symbol('edit'); ?>
        <?php echo T_("Rename");?>
    </button>
    <button onclick="UUTDeleteNode()">
        <?php echo Ui::get_material_symbol('close'); ?>
        <?php echo T_("Delete");?>
    </button>
    <button onclick="UUTReload()">
        <?php echo Ui::get_material_symbol('refresh'); ?>
        <?php echo T_("Reload");?>
    </button>
    <button onclick="UUTCut()">
        <?php echo Ui::get_material_symbol('cut'); ?>
        <?php echo T_("Cut");?>
    </button>
    <button onclick="UUTCopy()">
        <?php echo Ui::get_material_symbol('folder_copy'); ?>
        <?php echo T_("Copy");?>
    </button>
    <button onclick="UUTPaste()">
        <?php echo Ui::get_material_symbol('content_paste'); ?>
        <?php echo T_("Paste");?>
    </button>
</div>

<link rel="stylesheet" href="<?php echo $web_path; ?>/lib/components/wunderbaum/wunderbaum.css" type="text/css" media="screen">
<link rel="stylesheet" href="<?php echo $web_path; ?>/templates/file-upload.css" type="text/css" media="screen" />
<script src="<?php echo $web_path; ?>/lib/components/wunderbaum/wunderbaum.umd.min.js"></script>

<script>
(function() {
    let nodeBeingCut;
    let nodeBeingCopied;

    const tree = new mar10.Wunderbaum({
        debugLevel: 0,
        element: document.getElementById("uploadtree"),
        source: '<?php echo $ajaxfs; ?>?operation=get_node&id=<?php echo urlencode("#"); ?>',
        iconMap: {
            "error": "uut-icon uut-warning",
            "loading": "uut-icon uut-chevron-right wb-busy",
            "noData": "uut-icon uut-help",
            "expanderExpanded": "uut-icon uut-chevron-down",
            "expanderCollapsed": "uut-icon uut-chevron-right",
            "expanderLazy": "uut-icon uut-chevron-right wb-helper-lazy-expander",
            "checkChecked": "uut-icon uut-check-box",
            "checkUnchecked": "uut-icon uut-check-box-blank",
            "checkUnknown": "uut-icon uut-indeterminate-check-box",
            "radioChecked": "uut-icon uut-radio-button-checked",
            "radioUnchecked": "uut-icon uut-radio-button",
            "radioUnknown": "uut-icon uut-radio-button-partial",
            "folder": "uut-icon uut-folder",
            "folderOpen": "uut-icon uut-folder-open",
            "folderLazy": "uut-icon uut-folder-special",
            "doc": "uut-icon uut-description"
        },
        lazyLoad: function (e) {
            return { url: `<?php echo $ajaxfs; ?>?operation=get_node&id=${e.node.key}` };
        },
        activate: function (e) {
            document.getElementById("folder").value = e.node.key;
        },
        init: function (e) {
            e.tree.setActiveNode("/");
        },
        beforeExpand: function (e) {
            if (e.node.key === "/") return false;
        },
        dnd: {
            autoExpandMS: 300,
            preventNonNodes: true,
            dragStart: () => {
                return true;
            },
            dragEnter: () => {
                return true;
            },
            drop: async (e) => {
                let newParent = e.node;
                let draggedItem = e.sourceNode;

                moveNode(draggedItem, newParent);
            },
        },
        edit: {
            select: true,
            trim: true,
            apply: function (e) {
                fetch(`<?php echo $ajaxfs; ?>?operation=rename_node&id=${e.node.key}&text=${encodeURIComponent(e.newValue)}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(d => {
                        // One day we can update the key with:
                        // e.node.setKey(d.id, null);
                        // but until then just refetch the parent and reset the focus to the new item
                        e.node.parent.loadLazy(true)
                            .then(() => {
                                tree.findKey("/").setActive();
                                tree.findKey(d.id)?.setActive();
                            });
                    })
                    .catch(error => {
                        e.node.setTitle(e.oldValue);
                    });
            }
        }
    });

    function createNode() {
        getActiveNode().setExpanded();

        fetch('<?php echo $ajaxfs; ?>?operation=create_node&id=' + encodeURIComponent(getActiveNode().key) + '&text=<?php echo T_("New folder"); ?>')
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(async (d) => {
                const newNode = await tree.findKey(d.id) || await getActiveNode().addNode({ title: 'New folder', key: d.id, children: [] }, 'prependChild');
                setTimeout(function () {
                    newNode.startEditTitle();
                }, 100);
            })
    }

    function renameNode() {
        if (getActiveNode().key === "/") return;
        getActiveNode().startEditTitle();
    }

    function deleteNode() {
        if (getActiveNode().key === "/") return;

        let confirmed = window.confirm("Do you want to proceed with this action?");

        if (confirmed) {
            fetch('<?php echo $ajaxfs; ?>?operation=delete_node&id=' + encodeURIComponent(getActiveNode().key))
                .then(() => {
                    let parent = getActiveNode().parent;
                    getActiveNode().remove();
                    parent.setActive();
                })
        }
    }

    function getActiveNode() {
        return tree.activeNode || tree.findKey("/");
    }

    async function reloadTree() {
        await tree.load(tree.options.source);
        tree.setActiveNode("/");
        nodeBeingCut = null;
        nodeBeingCopied = null;
    }

    function moveNode(itemNode, destinationNode) {
        let finalParent = destinationNode.key === "/" ? tree.root : destinationNode;

        fetch(`<?php echo $ajaxfs; ?>?operation=move_node&id=${itemNode.key}&parent=${destinationNode.key}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(async (d) => {
                let sameNameExists = destinationNode.findDirectChild(itemNode.title);

                if (!sameNameExists) {
                    itemNode.moveTo(destinationNode, "appendChild");

                    // One day we can update the key with:
                    // e.node.setKey(d.id, null);
                    // but until then just refetch the parent

                    if (finalParent.isLazy()) {
                        await finalParent.loadLazy(true);
                    } else {
                        await tree.load(tree.options.source);
                    }

                    finalParent?.setExpanded();
                }
            })
            .catch(error => {
                reloadTree();
            });
    }

    function copyNode(itemNode, destinationNode) {
        let finalParent = destinationNode.key === "/" ? tree.root : destinationNode;

        fetch(`<?php echo $ajaxfs; ?>?operation=copy_node&id=${itemNode.key}&parent=${destinationNode.key}`)
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(async (d) => {
                if (finalParent.isLazy()) {
                    await finalParent.loadLazy(true);
                } else {
                    await tree.load(tree.options.source);
                }

                finalParent?.setExpanded();
            })
            .catch(error => {
                reloadTree();
            });
    }

    function requestCut() {
        nodeBeingCopied = null;
        nodeBeingCut = getActiveNode();
    }

    function requestCopy() {
        nodeBeingCut = null;
        nodeBeingCopied = getActiveNode();
    }

    function requestPaste() {
        // if cutting, perform move
        if (nodeBeingCut) {
            moveNode(nodeBeingCut, getActiveNode());
            nodeBeingCut = null;
        }

        // if copying, perform copy
        if (nodeBeingCopied) {
            copyNode(nodeBeingCopied, getActiveNode());
        }
    }

    // Expose functions globally
    // UUT = user upload tree
    window.UUTTree = tree;
    window.UUTCreateNode = createNode;
    window.UUTRenameNode = renameNode;
    window.UUTDeleteNode = deleteNode;
    window.UUTReload = reloadTree;
    window.UUTCut = requestCut;
    window.UUTCopy = requestCopy;
    window.UUTPaste = requestPaste;
})();
</script>

<table class="tabledata">
    <tr>
        <small>
            <?php echo Ui::get_material_symbol('info'); ?>
            <?php echo T_('Leave the artist and album fields blank to read file tags'); ?>
        </small>
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

<?php echo T_('Files'); ?>
<input type="file" class="filepond" name="upl" multiple>
<input type="hidden" id="folder" name="folder" value="" />

<small>
    <?php
    if ($upload_max > 0) {
        echo T_('Max upload size') . ": " . Ui::format_bytes($upload_max) . "<br>";
    } ?>
    <?php echo T_('Allowed file type'); ?>:
    <?php echo str_replace("|", ", ", AmpConfig::get('catalog_file_pattern')); ?>
</small>


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
