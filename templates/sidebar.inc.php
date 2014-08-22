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

$web_path = AmpConfig::get('web_path');
?>
<div id="sidebar" class="side-bar dark-scrollbar">
    <ul class="list dashboard-server-list">
        <li class="dashboard-server-list-item">
            <div class="side-bar-actions pull-right">
                <span id="music-dropdown" class="dropdown">
                <a rel="nohtml" class="btn-gray dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-ellipsis-h"></i>
                </a>
                <ul class="dropdown-menu pull-right">
                  <li><a class="song-music-btn" href="<?php echo $web_path; ?>/browse.php?action=song"><?php echo T_('Song Titles'); ?></a></li>
                  <li><a class="album-music-btn" href="<?php echo $web_path; ?>/browse.php?action=album"><?php echo T_('Albums'); ?></a></li>
                  <li><a class="tag-music-btn" href="<?php echo $web_path; ?>/browse.php?action=tag"><?php echo T_('Tag Cloud'); ?></a></li>
                  <li><a class="smart-music-btn" href="<?php echo $web_path; ?>/browse.php?action=smartplaylist"><?php echo T_('Smart Playlists'); ?></a></li>
                  <li><a class="channel-music-btn" href="<?php echo $web_path; ?>/browse.php?action=channel"><?php echo T_('Channels'); ?></a></li>
                  <?php if (AmpConfig::get('broadcast')) { ?>
                  <li><a class="broadcast-music-btn" href="<?php echo $web_path; ?>/browse.php?action=broadcast"><?php echo T_('Broadcasts'); ?></a></li>
                  <?php } ?>
                  <?php if (AmpConfig::get('allow_upload')) { ?>
                  <li><a class="upload-music-btn" href="<?php echo $web_path; ?>/upload.php"><?php echo T_('Upload'); ?></a></li>
                  <?php } ?>
                </ul>
              </span>
            </div>
            <h5><i class="section-icon fa fa-music"></i><?php echo T_('Browse Music'); ?></h5>
            <ul class="list side-bar-list dashboard-section-list">
                <li>
                    <a class="btn-gray" href="<?php echo $web_path; ?>/browse.php?action=artist">
                        <i class="section-icon fa fa-music"></i> <?php echo T_('Artists'); ?>
                    </a>
                </li>
                <li>
                    <a class="btn-gray" href="<?php echo $web_path; ?>/browse.php?action=playlist">
                        <i class="section-icon fa fa-music"></i> <?php echo T_('Playlists'); ?>
                    </a>
                </li>
                <li>
                    <a class="btn-gray" href="<?php echo $web_path; ?>/browse.php?action=live_stream">
                        <i class="section-icon fa fa-music"></i> <?php echo T_('Radio Stations'); ?>
                    </a>
                </li>
            </ul>
        </li>
        <?php if (AmpConfig::get('allow_video')) { ?>
        <li class="dashboard-server-list-item">
            <div class="side-bar-actions pull-right">
                <span id="movie-dropdown" class="dropdown">
                <a rel="nohtml" class="btn-gray dropdown-toggle" data-toggle="dropdown">
                    <i class="fa fa-ellipsis-h"></i>
                </a>
                <ul class="dropdown-menu pull-right">
                  <li><a class="song-music-btn" href="<?php echo $web_path; ?>/browse.php?action=tag&type=video"><?php echo T_('Tag Cloud'); ?></a></li>
                </ul>
              </span>
            </div>
            <h5><i class="section-icon fa fa-film"></i><?php echo T_('Browse Movie'); ?></h5>
            <ul class="list side-bar-list dashboard-section-list">
                <li>
                    <a class="btn-gray" href="<?php echo $web_path; ?>/browse.php?action=clip">
                        <i class="section-icon fa fa-music"></i> <?php echo T_('Music Clips'); ?>
                    </a>
                </li>
                <li>
                    <a class="btn-gray" href="<?php echo $web_path; ?>/browse.php?action=tvshow">
                        <i class="section-icon fa fa-desktop"></i> <?php echo T_('TV Shows'); ?>
                    </a>
                </li>
                <li>
                    <a class="btn-gray" href="<?php echo $web_path; ?>/browse.php?action=movie">
                        <i class="section-icon fa fa-film"></i> <?php echo T_('Movies'); ?>
                    </a>
                </li>
                <li>
                    <a class="btn-gray" href="<?php echo $web_path; ?>/browse.php?action=personal_video">
                        <i class="section-icon fa fa-video-camera"></i> <?php echo T_('Personal Videos'); ?>
                    </a>
                </li>
            </ul>
        </li>
        <?php } ?>
        
        <?php Ajax::start_container('browse_filters'); ?>
        <?php Ajax::end_container(); ?>
        
        <?php
        // TODO: Add other menu and add collapse feature
        ?>
    </ul>
</div>

