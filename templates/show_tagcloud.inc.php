<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

$web_path = Config::get('web_path');
?>
<?php Ajax::start_container('tag_filter'); ?>
<?php foreach ($object_ids as $data) {
    $tag = new Tag($data['id']);
    $tag->format();
?>
<span id="click_<?php echo intval($tag->id); ?>" class="<?php echo $tag->f_class; ?>"><?php echo $tag->name; ?></span>
<?php echo Ajax::observe('click_' . intval($tag->id),'click',Ajax::action('?page=tag&action=add_filter&browse_id=' . $browse2->id . '&tag_id=' . intval($tag->id),'')); ?>
<?php } ?>
<?php if (!count($object_ids)) { ?>
<span class="fatalerror"><?php echo T_('Not Enough Data'); ?></span>
<?php } ?>
<?php Ajax::end_container(); ?>
