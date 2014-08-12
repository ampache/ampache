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

/**
 * List Header
 * The default pager widget for moving through a list of many items.
 * This relies heavily on the View object to get pieces about how
 * to layout this page.
 */

 if (isset($is_header) && $is_header) {
    $is_header = false;
 } else {
    $is_header = true;
 }

// Pull these variables out to allow shorthand (easier for lazy programmers)
$limit    = $browse->get_offset();
$start    = $browse->get_start();
$total    = $browse->get_total();
if (isset($_REQUEST['browse_uid'])) {
    $uid = $_REQUEST['browse_uid']++;
} else {
    $uid = AmpConfig::get('list_header_uid');
    AmpConfig::set('list_header_uid', ++$uid, true);
}
$sides  = 0;

?>
<?php if (!$browse->get_use_pages() && !$is_header) { ?>
<?php $this->show_next_link(); ?>
</p>
</div>
<script type="text/javascript">
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
if ($next_offset > $total) { $next_offset = $start; }

// Prev
$prev_offset = $start - $limit;
if ($prev_offset < 0) { $prev_offset = '0'; }

/* Calculate how many pages total exist */
if ($limit > 0 && $total > $limit) {
    $pages = ceil($total / $limit);
} else {
    $pages = 0;
}
?>
<div class="list-header">
<?php if ($browse->get_use_alpha()) { ?>
    <div class="list-header-alpha">
    <?php
    $alphastr = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $alphalist = str_split($alphastr);
    $alphalist[] = '#';
    foreach ($alphalist as $key => $value) {
        $filter = '^';
        if ($value == '#') {
            $filter .= '[[:digit:]|[:punct:]]';
        } else {
            $filter .= $value;
        }
        if ($browse->get_filter('regex_match') == $filter) {
            $value = '<b>' . $value . '</b>';
        }
        echo Ajax::text('?page=browse&action=browse&browse_id=' . $browse->id . '&key=regex_match&multi_alpha_filter=' . $filter, $value,'browse_' . $uid . '_alpha_' . $key,'');
    }
    ?>
    </div>
<?php } ?>
<?php
// are there enough items to even need this view?
if ($pages > 1 && $start > -1) {

    /* Calculate current page and how many we have on each side */
    $page_data = array('up' => array(), 'down' => array());

    // Can't divide by 0
    if ($start > 0) {
        $current_page = floor($start / $limit);
    } else {
        $current_page = 0;
    }

    // Create 10 pages in either direction
    // Down first
    $page = $current_page;
    $i = 0;
    while ($page > 0) {
        if ($i == $sides) { $page_data['down'][1] = T_('of'); $page_data['down'][0] = '0'; break; }
        $i++;
        $page = $page - 1;
        $page_data['down'][$page] = $page * $limit;
    } // while page > 0

    // Then up
    $page = $current_page + 1;
    $i = 0;
    while ($page < $pages) {
        if ($page * $limit > $total) { break; }
        if ($i == $sides) {
            $key = $pages - 1;
            if (!$page_data['up'][$key]) { $page_data['up'][$key] = T_('of'); }
            $page_data['up'][$pages] = ($pages - 1) * $limit;
            break;
        }
        $i++;
        $page = $page + 1;
        $page_data['up'][$page] = ($page - 1) * $limit;
    } // end while

    // Sort these arrays of hotness
    ksort($page_data['up']);
    ksort($page_data['down']);
?>
<?php if ($browse->get_use_pages()) { ?>
    <span class="list-header-navmenu-border">
    <span><?php echo Ajax::text('?page=browse&action=page&browse_id=' . $browse->id . '&start=' . $prev_offset . '&browse_uid=' . $uid, T_('Prev'),'browse_' . $uid . 'prev','','prev'); ?></span>
    &nbsp;
    <?php
        /* Echo current page */
        $current_page++;
    ?>
    <?php echo '&nbsp;' . T_('Page') . ':' ?>
    <input class="list-header-navmenu-input" type="text" id="browse_<?php echo $browse->id; ?>_custom_value_<?php echo $is_header; ?>" class="browse_custom_value" name="value" value="<?php echo $current_page; ?>" onKeyUp="delayRun(this, '750', 'ajaxState', '<?php echo Ajax::url('?page=browse&action=options&browse_id=' . $browse->id . '&option=custom'); ?>', 'browse_<?php echo $browse->id; ?>_custom_value_<?php echo $is_header; ?>');">
    <?php
        /* Echo everything above us */
        foreach ($page_data['up'] as $page=>$offset) {
            if ($offset === T_('of')) { echo T_('of') . '&nbsp;'; } else {
                echo Ajax::text('?page=browse&action=page&browse_id=' . $browse->id . '&start=' . $offset . '&browse_uid=' . $uid,$page,'browse_' . $uid . 'page_' . $page,'','page-nb');
            } // end else
        } // end foreach up
    ?>
    &nbsp;
    <span><?php echo Ajax::text('?page=browse&action=page&browse_id=' . $browse->id . '&start=' . $next_offset . '&browse_uid=' . $uid, T_('Next'),'browse_' . $uid . 'next','','next'); ?></span>
    &nbsp;
    <span><?php echo Ajax::text('?page=browse&action=page&browse_id=' . $browse->id . '&start=-1&browse_uid=' . $uid, T_('All'),'browse_' . $uid . 'all','','all'); ?></span>
    </span>
<?php
    }
} // if stuff
?>
    <span class="browse-options">
        <a href="#" onClick="showFilters(this);" class="browse-options-link"><?php echo T_("Filters"); ?></a>
        <span class="browse-options-content">
            <span><input type="checkbox" id="browse_<?php echo $browse->id; ?>_use_pages_<?php echo $is_header; ?>" value="true" <?php echo (($browse->get_use_pages()) ? 'checked' : ''); ?> onClick="javascript:<?php echo Ajax::action("?page=browse&action=options&browse_id=" . $browse->id . "&option=use_pages&value=' + $('#browse_" . $browse->id . "_use_pages_" . $is_header . "').is(':checked') + '", "browse_" . $browse->id . "_use_pages_" . $is_header); ?>">Pages</span>
            <span><input type="checkbox" id="browse_<?php echo $browse->id; ?>_use_scroll_<?php echo $is_header; ?>" value="true" <?php echo ((!$browse->get_use_pages()) ? 'checked' : ''); ?> onClick="javascript:<?php echo Ajax::action("?page=browse&action=options&browse_id=" . $browse->id . "&option=use_pages&value=' + !($('#browse_" . $browse->id . "_use_scroll_" . $is_header . "').is(':checked')) + '", "browse_" . $browse->id . "_use_scroll_" . $is_header); ?>">Infinite Scroll</span>
            <span><input type="checkbox" id="browse_<?php echo $browse->id; ?>_use_alpha_<?php echo $is_header; ?>" value="true" <?php echo (($browse->get_use_alpha()) ? 'checked' : ''); ?> onClick="javascript:<?php echo Ajax::action("?page=browse&action=options&browse_id=" . $browse->id . "&option=use_alpha&value=' + $('#browse_" . $browse->id . "_use_alpha_" . $is_header . "').is(':checked') + '", "browse_" . $browse->id . "_use_alpha_" . $is_header); ?>">Alphabet</span>
        <?php if ($browse->get_use_pages()) { ?>
            <span>|</span>
            <span>
                <form id="browse_<?php echo $browse->id; ?>_limit_form_<?php echo $is_header; ?>" method="post" action="javascript:void(0);">
                    <label id="limit_label_<?php echo $browse->id; ?>_<?php echo $is_header; ?>" for="multi_alpha_filter"><?php echo T_('Limit'); ?>:</label>
                    <input type="text" id="limit_value_<?php echo $browse->id; ?>_<?php echo $is_header; ?>" name="value" value="<?php echo $browse->get_offset(); ?>" onKeyUp="delayRun(this, '800', 'ajaxState', '<?php echo Ajax::url('?page=browse&action=options&browse_id=' . $browse->id . '&option=limit'); ?>', 'limit_value_<?php echo $browse->id; ?>_<?php echo $is_header; ?>');">
                </form>
            </span>
        <?php } ?>
        </span>
    </span>
</div>
<span class="item-count"><?php echo T_('Item Count') . ': ' . $total; ?></span>
<?php if (!$browse->get_use_pages() && $is_header) { ?>
<div id="browse_<?php echo $browse->id; ?>_scroll">
<p>
<?php } ?>
