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

/**
 * List Header
 * The default pager widget for moving through a list of many items.
 * This relies heavily on the View object to get pieces about how
 * to layout this page.
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\Api\Ajax;
use Ampache\Repository\Model\Browse;

/** @var Browse $browse */

if (isset($is_header) && $is_header) {
    $is_header = false;
} else {
    $is_header = true;
}
if (isset($hide_view) && $hide_view) {
    $hide_view = true;
} else {
    $hide_view = false;
}
// album grouped by release type
if (isset($group_release) && $group_release) {
    $group_release = true;
} else {
    $group_release = false;
}
$argument_param = $argument_param ?? '';

// Pull these variables out to allow shorthand (easier for lazy programmers)
$limit = $browse->get_offset();
$start = $browse->get_start();
$total = $browse->get_total();
if (array_key_exists('browse_uid', $_REQUEST)) {
    $uid = $_REQUEST['browse_uid']++;
} else {
    $uid = AmpConfig::get('list_header_uid');
    AmpConfig::set('list_header_uid', ++$uid, true);
}
$sides = 0;
?>
<?php if (!$browse->is_use_pages() && !$is_header) { ?>
<?php $browse->show_next_link(); ?>
</p>
</div>
<script>
$('#browse_<?php echo $browse->id; ?>_scroll').jscroll({
    autoTrigger: true,
    nextSelector: 'a.jscroll-next:last',
    autoTriggerUntil: 5,
});
</script>
<?php } ?>
<?php

// Next
$next_offset = $start + $limit;
if ($next_offset >= $total) {
    $next_offset = $start;
}

// Prev
$prev_offset = $start - $limit;
if ($prev_offset < 0) {
    $prev_offset = '0';
}

/* Calculate how many pages total exist */
if ($limit > 0 && $total > $limit) {
    $pages = ceil($total / $limit);
} else {
    $pages = 0;
} ?>
<div class="list-header">
<?php if ($browse->is_use_alpha()) { ?>
    <div class="list-header-alpha">
    <?php
    $alphastr    = AmpConfig::get('alpha_string_pattern', "ABCDEFGHIJKLMNOPQRSTUVWXYZ");
    $alphalist   = str_split($alphastr);
    $alphalist[] = '#';
    $alphalist[] = '*';
    foreach ($alphalist as $key => $value) {
        $filter = '^';
        if ($value == '#') {
            $filter .= '[[:digit:]|[:punct:]]';
        } elseif ($value == '*') {
            $filter .= '.*';
        } else {
            $filter .= $value;
        }
        if ($browse->get_filter('regex_match') == $filter) {
            $value = '<b>' . scrub_out($value) . '</b>';
        }
        echo Ajax::text('?page=browse&action=browse&browse_id=' . $browse->id . '&key=regex_match&multi_alpha_filter=' . $filter . $argument_param, $value, 'browse_' . $uid . '_alpha_' . $key);
    } ?>
    </div>
<?php } ?>
<?php if ($pages > 1 && $start > -1) {
    $current_page = 0;
    if ($start > 0) {
        $current_page = floor($start / $limit);
    }

    if ($browse->is_use_pages()) { ?>
    <span class="list-header-navmenu-border">
    <span><?php echo Ajax::text('?page=browse&action=page&browse_id=' . $browse->id . '&start=' . $prev_offset . '&browse_uid=' . $uid . $argument_param, T_('Prev'), 'browse_' . $uid . 'prev', '', 'prev'); ?></span>
    &nbsp;
    <span class="page-text"><?php echo '&nbsp;' . T_('Page') . ':'; ?></span>
    <input class="list-header-navmenu-input" type="text" id="browse_<?php echo $browse->id; ?>_custom_value_<?php echo $is_header; ?>" class="browse_custom_value" name="value" value="<?php echo($current_page + 1); ?>">
    <script>
        $('#browse_<?php echo $browse->id; ?>_custom_value_<?php echo $is_header; ?>').on('blur keydown',function(e) {
            if (e.type === 'blur' || e.key === "Enter") {
                delayRun(this, '50', 'ajaxState', '<?php echo Ajax::url('?page=browse&action=options&browse_id=' . $browse->id . '&option=custom' . $argument_param); ?>', 'browse_<?php echo $browse->id; ?>_custom_value_<?php echo $is_header; ?>')
            }
        });
    </script>
    <span class="page-text"><?php echo T_('of') . '&nbsp;' . $pages; ?></span>
    &nbsp;
    <span><?php echo Ajax::text('?page=browse&action=page&browse_id=' . $browse->id . '&start=' . $next_offset . '&browse_uid=' . $uid . $argument_param, T_('Next'), 'browse_' . $uid . 'next', '', 'next'); ?></span>
    &nbsp;
    <span><?php echo Ajax::text('?page=browse&action=page&browse_id=' . $browse->id . '&start=-1&browse_uid=' . $uid . $argument_param, T_('All'), 'browse_' . $uid . 'all', '', 'all'); ?></span>
    </span>
<?php }
    } ?>
    <span class="browse-options">
<?php if (!$hide_view) { ?>
            <a href="javascript:showFilters(this, '<?php echo '_' . $browse->id; ?>', <?php echo ($group_release) ? 'true' : 'false'; ?>);" class="browse-options-link" id="browse-options-link_<?php echo $browse->id; ?>"><?php echo T_("View"); ?></a>
            <span class="browse-options-content" id="browse-options-content_<?php echo $browse->id; ?>">
            <span><input type="checkbox" id="browse_<?php echo $browse->id; ?>_use_pages_<?php echo $is_header; ?>" value="true" <?php echo(($browse->is_use_pages()) ? 'checked' : ''); ?> onClick="javascript:<?php echo Ajax::action("?page=browse&action=options&browse_id=" . $browse->id . "&option=use_pages&value=' + $('#browse_" . $browse->id . "_use_pages_" . $is_header . "').is(':checked') + '" . $argument_param, "browse_" . $browse->id . "_use_pages_" . $is_header); ?>"><?php echo T_('Pages'); ?></span>
            <span><input type="checkbox" id="browse_<?php echo $browse->id; ?>_use_scroll_<?php echo $is_header; ?>" value="true" <?php echo((!$browse->is_use_pages()) ? 'checked' : ''); ?> onClick="javascript:<?php echo Ajax::action("?page=browse&action=options&browse_id=" . $browse->id . "&option=use_pages&value=' + !($('#browse_" . $browse->id . "_use_scroll_" . $is_header . "').is(':checked')) + '" . $argument_param, "browse_" . $browse->id . "_use_scroll_" . $is_header); ?>"><?php echo T_('Infinite Scroll'); ?></span>
            <?php if (in_array($browse->get_type(), ['song', 'album', 'album_disk', 'artist', 'live_stream', 'playlist', 'smartplaylist', 'video', 'podcast', 'podcast_episode'])) { ?>
            <span><input type="checkbox" id="browse_<?php echo $browse->id; ?>_grid_view_<?php echo $is_header; ?>" value="true" <?php echo(($browse->is_grid_view()) ? 'checked' : ''); ?> onClick="javascript:<?php echo Ajax::action("?page=browse&action=options&browse_id=" . $browse->id . "&object_type=" . $browse->get_type() . "&option=grid_view&value=' + ($('#browse_" . $browse->id . "_grid_view_" . $is_header . "').is(':checked')) + '" . $argument_param, "browse_" . $browse->id . "_grid_view_" . $is_header); ?>"><?php echo T_('Grid View'); ?></span>
            <?php } elseif (!$browse->is_mashup()) {
                $browse->set_grid_view(false);
            } ?>
            <?php if (!$browse->is_static_content() && $browse->is_use_filters()) { ?>
            <span><input type="checkbox" id="browse_<?php echo $browse->id; ?>_use_alpha_<?php echo $is_header; ?>" value="true" <?php echo(($browse->is_use_alpha()) ? 'checked' : ''); ?> onClick="javascript:<?php echo Ajax::action("?page=browse&action=options&browse_id=" . $browse->id . "&option=use_alpha&value=' + $('#browse_" . $browse->id . "_use_alpha_" . $is_header . "').is(':checked') + '" . $argument_param, "browse_" . $browse->id . "_use_alpha_" . $is_header); ?>"><?php echo T_('Alphabet'); ?></span>
            <?php } ?>
        <?php if ($browse->is_use_pages()) { ?>
            <span>|</span>
            <span>
                <form id="browse_<?php echo $browse->id; ?>_limit_form_<?php echo $is_header; ?>" method="post" action="javascript:void(0);">
                    <label id="limit_label_<?php echo $browse->id; ?>_<?php echo $is_header; ?>" for="multi_alpha_filter"><?php echo T_('Limit'); ?>:</label>
                    <input type="text" id="limit_value_<?php echo $browse->id; ?>_<?php echo $is_header; ?>" name="value" value="<?php echo $browse->get_offset(); ?>">
                    <script>
                        $('#limit_value_<?php echo $browse->id; ?>_<?php echo $is_header; ?>').on('blur keydown',function(e) {
                            if (e.type === 'blur' || e.key === "Enter") {
                                delayRun(this, '50', 'ajaxState', '<?php echo Ajax::url('?page=browse&action=options&browse_id=' . $browse->id . '&option=limit'); ?>', 'limit_value_<?php echo $browse->id; ?>_<?php echo $is_header; ?>');
                            }
                        });
                    </script>
                </form>
            </span>
        <?php } ?>
        <a href="javascript:hideFilters(this, '<?php echo '_' . $browse->id; ?>', <?php echo ($group_release) ? 'true' : 'false'; ?>);" class="browse-options-hidelink" id="browse-options-hidelink_<?php echo $browse->id; ?>" style="display:none;"><?php echo T_('View'); ?></a>
        </span>
    </span>
<?php } ?>
</div>
<span class="item-count"><?php echo T_('Item Count') . ': ' . $total; ?></span>
<?php if (!$browse->is_use_pages() && $is_header) { ?>
<div id="browse_<?php echo $browse->id; ?>_scroll">
<p>
<?php } ?>
