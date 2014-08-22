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

<div class="nav-bar">
    <ul class="nav nav-bar-nav">
        <li>
            <a class="home-btn" href="<?php echo $web_path; ?>/index.php" title="<?php echo AmpConfig::get('site_title'); ?>" alt="<?php echo AmpConfig::get('site_title'); ?>">
                <i class="fa fa-home fa-lg"></i>
            </a>
        </li>
    </ul>

    <div class="nav-bar-search-container">
        <form id="nav-bar-search-form" class="nav-bar-form nav-bar-left hidden-xs" method="post" action="<?php echo $web_path; ?>/search.php?type=song" enctype="multipart/form-data">
            <div class="form-group form-group-search">
                <label class="control-label-search" for="nav-bar-search">
                    <i class="fa fa-search fa-lg"></i>
                    <a class="clear-search-btn hidden" href="#"><i class="fa fa-times-circle"></i></a>
                </label>

                <input type="search" id="nav-bar-search" class="form-control form-control-search" placeholder="<?php echo T_("Search"); ?>" value="">
            </div>
        </form>
    </div>

    <ul class="nav nav-bar-nav nav-bar-right">
        <?php
        if (AmpConfig::get('autoupdate') && Access::check('interface','100')) {
            if (AutoUpdate::is_update_available() && AutoUpdate::is_git_repository()) {
        ?>
        <li class="">
            <a class="install-updates-btn" title="<?php echo T_('Mises à jour disponibles'); ?>" data-toggle="tooltip" rel="nohtml" href="<?php echo (AmpConfig::get('web_path') . '/update.php?type=sources&action=update'); ?>">
                <i class="fa fa-up-arrow fa-lg"></i>
            </a>
        </li>
        <?php
            }
        }
        ?>
        
        <?php
        // Localplay
        $server_allow = AmpConfig::get('allow_localplay_playback');
        $controller = AmpConfig::get('localplay_controller');
        $access_check = Access::check('localplay','5');
        if ($server_allow && $controller && $access_check) {
            
            $localplay = new Localplay(AmpConfig::get('localplay_controller'));
            $current_instance = $localplay->current_instance();
            $class = $current_instance ? '' : 'active_instance';
        ?>
        <li id="nav-localplay-dropdown" class="nav-dropdown dropdown">
            <a rel="nohtml" class="dropdown-toggle" data-toggle="dropdown" data-original-title="" title="<?php echo T_('Localplay'); ?>">
                <i class="fa fa-tasks fa-lg"></i><i class="caret-icon"></i>
            </a>
            <ul class="dropdown-menu menu-localplay">
                <li role="presentation" class="dropdown-header"><?php echo T_('Localplay Settings'); ?></li>
                <li class="menu-localplay-item">
                    <a href="<?php echo $web_path; ?>/localplay.php?action=show_add_instance"><?php echo T_('Add instance'); ?></a>
                </li>
                <li class="menu-localplay-item">
                    <a href="<?php echo $web_path; ?>/localplay.php?action=show_instances"><?php echo T_('Show instances'); ?></a>
                </li>
                <li class="menu-localplay-item">
                    <a href="<?php echo $web_path; ?>/localplay.php?action=show_playlist"><?php echo T_('Show Playlist'); ?></a>
                </li>

                <li class="menu-localplay-item divider"></li>
                
                <li class="menu-localplay-item <?php echo $class; ?>">
                    <?php echo Ajax::text('?page=localplay&action=set_instance&instance=0', T_('None'), '');  ?>
                </li>
                <?php
                    $instances = $localplay->get_instances();
                    foreach ($instances as $uid=>$name) {
                        $name = scrub_out($name);
                        $class = ($uid == $current_instance) ? 'active_instance' : '';
                ?>
                    <li class="menu-localplay-item <?php echo $class; ?>">
                        <?php echo Ajax::text('?page=localplay&action=set_instance&instance=' . $uid, $name, ''); ?>
                    </li>
                <?php } ?>
            </ul>
        </li>
        <?php } ?>
        
        <?php
        // Modules
        if (Access::check('interface','100')) {
        ?>
        <li id="nav-modules-dropdown" class="nav-dropdown dropdown">
            <a rel="nohtml" class="dropdown-toggle" data-toggle="dropdown" data-original-title="" title="<?php echo T_('Modules'); ?>">
                <i class="fa fa-cube fa-lg"></i><i class="caret-icon"></i>
            </a>
            <ul class="dropdown-menu menu-modules">
                <li role="presentation" class="dropdown-header"><?php echo T_('Modules Settings'); ?></li>
                <li class="menu-modules-item">
                    <a href="<?php echo $web_path; ?>/admin/modules.php?action=show_localplay"><?php echo T_('Localplay Modules'); ?></a>
                </li>
                <li class="menu-modules-item">
                    <a href="<?php echo $web_path; ?>/admin/modules.php?action=show_catalog_types"><?php echo T_('Catalog Modules'); ?></a>
                </li>
                <li class="menu-modules-item">
                    <a href="<?php echo $web_path; ?>/admin/modules.php?action=show_plugins"><?php echo T_('Available Plugins'); ?></a>
                </li>

                <li class="menu-modules-item divider"></li>
                
                <li class="menu-modules-item">
                    <a href="<?php echo $web_path; ?>/admin/duplicates.php"><?php echo T_('Find Duplicates'); ?></a>
                </li>
                <li class="menu-modules-item">
                    <a href="<?php echo $web_path; ?>/admin/mail.php"><?php echo T_('Mail Users'); ?></a>
                </li>
            </ul>
        </li>
        <?php } ?>
        
        <?php
        // Administrator
        if (Access::check('interface','100')) {
        ?>
        <li id="nav-admin-dropdown" class="nav-dropdown dropdown">
            <a rel="nohtml" class="dropdown-toggle" data-toggle="dropdown" data-original-title="" title="<?php echo T_('Admin'); ?>">
                <i class="fa fa-cogs fa-lg"></i><i class="caret-icon"></i>
            </a>
            <ul class="dropdown-menu menu-admin">
                <li class="menu-admin-item">
                    <a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_catalogs"><?php echo T_('Catalogs'); ?></a>
                </li>
                <li class="menu-admin-item">
                    <a href="<?php echo $web_path; ?>/admin/users.php"><?php echo T_('Users'); ?></a>
                </li>
                <li class="menu-admin-item">
                    <a href="<?php echo $web_path; ?>/admin/access.php"><?php echo T_('Access Control'); ?></a>
                </li>

                <li class="menu-admin-item divider"></li>
                
                <li class="menu-admin-item">
                    <a href="<?php echo $web_path; ?>/admin/system.php?action=show_debug"><?php echo T_('Ampache Debug'); ?></a>
                </li>
                <li class="menu-admin-item">
                    <a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_now_playing"><?php echo T_('Clear Now Playing'); ?></a>
                </li>
                <li class="menu-admin-item">
                    <a href="<?php echo $web_path; ?>/admin/export.php"><?php echo T_('Export Catalog'); ?></a>
                </li>
                <?php if (AmpConfig::get('sociable')) { ?>
                    <li class="menu-admin-item">
                        <a href="<?php echo $web_path; ?>/admin/shout.php"><?php echo T_('Manage Shoutbox'); ?></a>
                    </li>
                <?php } ?>
                <?php if (AmpConfig::get('licensing')) { ?>
                    <li class="menu-admin-item">
                        <a href="<?php echo $web_path; ?>/admin/license.php"><?php echo T_('Manage Licenses'); ?></a>
                    </li>
                <?php } ?>

                <?php if (Access::check('interface','100')) { ?>
                    <li class="menu-admin-item divider"></li>
                    <li role="presentation" class="dropdown-header"><?php echo T_('Server Settings'); ?></li>
                    <?php
                        $catagories = Preference::get_catagories();
                        foreach ($catagories as $name) {
                            $f_name = ucfirst($name);
                    ?>
                    <li class="menu-admin-item">
                        <a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=<?php echo $name; ?>"><?php echo T_($f_name); ?></a>
                    </li>
                    <?php } ?>
                <?php } ?>
            </ul>
        </li>
        <?php } ?>
        
        <li id="nav-user-dropdown" class="nav-dropdown dropdown">
            <a rel="nohtml" class="dropdown-toggle" data-toggle="dropdown" data-original-title="" title="">
                <i class="fa fa-user fa-lg"></i><i class="caret-icon"></i>
                <span class="total-badge badge hidden">0</span>
            </a>

            <ul class="dropdown-menu menu-user">
                <li class="menu-user-item">
                    <a class="username-btn" href="<?php echo $web_path; ?>/preferences.php?tab=account"><?php echo $GLOBALS['user']->fullname; ?></a>
                </li>
                
                <li class="menu-user-item divider"></li>
                
                <?php if (AmpConfig::get('userflags')) { ?>
                <li class="menu-user-item">
                    <a class="username-btn" href="<?php echo $web_path; ?>/stats.php?action=userflag"><?php echo T_('My favorites'); ?></a>
                </li>
                
                <li class="menu-user-item divider"></li>
                <?php } ?>

                <li role="presentation" class="dropdown-header"><?php echo T_('My Settings'); ?></li>
                
                <?php
                    $catagories = Preference::get_catagories();
                    foreach ($catagories as $name) {
                        if ($name == 'system') { continue; }
                        $f_name = ucfirst($name);
                ?>
                <li class="menu-user-item">
                    <a href="<?php echo $web_path; ?>/preferences.php?tab=<?php echo $name; ?>"><?php echo T_($f_name); ?></a>
                </li>
                <?php } ?>

                <li class="menu-user-item divider"></li>

                <li class="menu-user-item"><a class="sign-out-btn" rel="nohtml" href="<?php echo $web_path; ?>/logout.php"><?php echo T_('Log out'); ?></a></li>
            </ul>
        </li>
    </ul>
</div>