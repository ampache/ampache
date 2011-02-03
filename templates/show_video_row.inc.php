<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Show Video Row
 *
 * PHP version 5
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
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
 * @category	Template
 * @package	Template
 * @author	Karl Vollmer <vollmer@ampache.org>
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @version	PHP 5.2
 * @link	http://www.ampache.org/
 * @since	File available since Release 1.0
 */

?>
<td class="cel_add">
	<?php echo Ajax::button('?action=basket&type=video&id=' . $video->id,'add',_('Add'),'add_video_' . $video->id); ?>
</td>
<td class="cel_title"><?php echo $video->f_title; ?></td>
<td class="cel_codec"><?php echo $video->f_codec; ?></td>
<td class="cel_resolution"><?php echo $video->f_resolution; ?></td>
<td class="cel_length"><?php echo $video->f_length; ?></td>
<td class="cel_tags"><?php $video->f_tags; ?></td>
<td class="cel_action">
<?php if (Access::check_function('download')) { ?>
	<a href="<?php echo Config::get('web_path'); ?>/stream.php?action=download&type=video&oid=<?php echo $video->id; ?>"><?php echo get_user_icon('download',_('Download')); ?></a>
<?php } ?>
</td>
