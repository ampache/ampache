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
 */ ?>
<?php UI::show_box_top($episode->f_title . ' - ' . $episode->f_podcast_link, 'box box_podcast_episode_details'); ?>
<dl class="media_details">

<?php if (User::is_registered()) { ?>
    <?php if (AmpConfig::get('ratings')) { ?>
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"><?php echo T_('Rating'); ?></dt>
        <dd class="<?php echo $rowparity; ?>">
            <div id="rating_<?php echo $episode->id; ?>_podcast_episode"><?php Rating::show($episode->id, 'podcast_episode'); ?>
            </div>
        </dd>
    <?php
    } ?>

    <?php if (AmpConfig::get('userflags')) { ?>
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"><?php echo T_('Fav.'); ?></dt>
        <dd class="<?php echo $rowparity; ?>">
            <div id="userflag_<?php echo $episode->id; ?>_podcast_episode"><?php Userflag::show($episode->id, 'podcast_episode'); ?>
            </div>
        </dd>
    <?php
    } ?>
<?php
} ?>
<?php $rowparity = UI::flip_class(); ?>
<dt class="<?php echo $rowparity; ?>"><?php echo T_('Action'); ?></dt>
    <dd class="<?php echo $rowparity; ?>">
        <?php if (!empty($episode->file)) { ?>
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episode->id, 'play', T_('Play'), 'play_podcast_episode_' . $episode->id); ?>
            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episode->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_podcast_episode_' . $episode->id); ?>
            <?php
            } ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episode->id . '&append=true', 'play_add', T_('Play last'), 'addplay_podcast_episode_' . $episode->id); ?>
            <?php
            } ?>
        <?php
        } ?>
        <?php echo Ajax::button('?action=basket&type=podcast_episode&id=' . $episode->id, 'add', T_('Add to temporary playlist'), 'add_podcast_episode_' . $episode->id); ?>
        <?php
    } ?>
        <?php if (!AmpConfig::get('use_auth') || Access::check('interface', 25)) { ?>
            <?php if (AmpConfig::get('sociable')) { ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=podcast_episode&id=<?php echo $episode->id; ?>">
                <?php echo UI::get_icon('comment', T_('Post Shout')); ?>
                </a>
            <?php
        } ?>
        <?php
    } ?>
        <?php if (Access::check('interface', 25)) { ?>
            <?php if (AmpConfig::get('share')) { ?>
                <?php Share::display_ui('podcast_episode', $episode->id, false); ?>
            <?php
        } ?>
        <?php
    } ?>
        <?php if (Access::check_function('download') && !empty($episode->file)) { ?>
            <a class="nohtml" href="<?php echo print_r($episode->play_url()); ?>"><?php echo UI::get_icon('link', T_('Link')); ?></a>
            <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/stream.php?action=download&amp;podcast_episode_id=<?php echo $episode->id; ?>"><?php echo UI::get_icon('download', T_('Download')); ?></a>
        <?php
    } ?>
        <?php if (Access::check('interface', 50)) { ?>
            <?php if (AmpConfig::get('statistical_graphs') && is_dir(AmpConfig::get('prefix') . '/lib/vendor/szymach/c-pchart/src/Chart/')) { ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/stats.php?action=graph&object_type=podcast_episode&object_id=<?php echo $episode->id; ?>"><?php echo UI::get_icon('statistics', T_('Graphs')); ?></a>
            <?php
        } ?>
            <a onclick="showEditDialog('podcast_episode_row', '<?php echo $episode->id ?>', '<?php echo 'edit_podcast_episode_' . $episode->id ?>', '<?php echo T_('Podcast Episode Edit') ?>', '')">
                <?php echo UI::get_icon('edit', T_('Edit')); ?>
            </a>
        <?php
    } ?>
        <?php if (Catalog::can_remove($episode)) { ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/podcast_episode.php?action=delete&podcast_episode_id=<?php echo $episode->id; ?>">
                <?php echo UI::get_icon('delete', T_('Delete')); ?>
            </a>
        <?php
    } ?>
    </dd>
<?php
    $songprops[T_('Title')]                  = $episode->f_title;
    $songprops[T_('Description')]            = $episode->f_description;
    $songprops[T_('Category')]               = $episode->f_category;
    $songprops[T_('Author')]                 = $episode->f_author;
    $songprops[T_('Publication Date')]       = $episode->f_pubdate;
    $songprops[T_('State')]                  = $episode->f_state;
    $songprops[T_('Website')]                = $episode->f_website;
    if ($episode->time > 0) {
        $songprops[T_('Length')]           = $episode->f_time;
    }

    if (!empty($episode->file)) {
        $songprops[T_('File')] = $episode->file;
        $songprops[T_('Size')] = $episode->f_size;
    }

    foreach ($songprops as $key => $value) {
        if (trim($value)) {
            $rowparity = UI::flip_class();
            echo "<dt class=\"" . $rowparity . "\">" . T_($key) . "</dt><dd class=\"" . $rowparity . "\">" . $value . "</dd>";
        }
    } ?>
</dl>
<?php UI::show_box_bottom(); ?>
