<h1><?php echo T_('Use of cookies by Ampache'); ?></h1>
<br />
<p>
<?php echo T_('Cookies are small text files that are placed on your computer by websites that you visit. They are widely used in order to make websites work, or work more efficiently, as well as to provide information to the owners of the site'); ?><br />
<?php echo T_('The table below explains the cookies we use and why'); ?><br />
</p>
<br /><br />

<table class="tabledata">
    <thead>
        <tr>
            <td>
                <h2><?php echo T_('Cookie'); ?></h2>
            </td>
            <td>
                <h2><?php echo T_('Name'); ?></h2>
            </td>
            <td>
                <h2><?php echo T_('Purpose'); ?></h2>
            </td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Cookie Disclaimer</td>
            <td>cookie_disclaimer</td>
            <td><?php echo T_('Hide the cookie disclaimer message'); ?></td>
        </tr>
        <tr>
            <td>Session</td>
            <td><?php echo AmpConfig::get('session_name'); ?></td>
            <td><?php echo T_('Ampache session'); ?></td>
        </tr>
        <tr>
            <td>Session username</td>
            <td><?php echo AmpConfig::get('session_name'); ?>_user</td>
            <td><?php echo T_('Ampache session username (if authenticated, information only)'); ?></td>
        </tr>
        <tr>
            <td>Remember Me</td>
            <td><?php echo AmpConfig::get('session_name'); ?>_remember</td>
            <td><?php echo T_('Automatically authenticate users'); ?></td>
        </tr>
        <tr>
            <td>jPlayer volume</td>
            <td>jp_volume</td>
            <td><?php echo T_('Keep latest web player volume'); ?></td>
        </tr>
        <tr>
            <td>Browse [object_type] Column [column_index]</td>
            <td>mt_[object_type]_[column_index]</td>
            <td><?php echo T_('Show/Hide column [column_index] when browsing [object_type] objects'); ?></td>
        </tr>
        <tr>
            <td>Browse [object_type] Alpha</td>
            <td>browse_[object_type]_alpha</td>
            <td><?php echo T_('Use alphabet when browsing [object_type] objects'); ?></td>
        </tr>
        <tr>
            <td>Browse [object_type] Pages</td>
            <td>browse_[object_type]_pages</td>
            <td><?php echo T_('Use pages when browsing [object_type] objects'); ?></td>
        </tr>
        <tr>
            <td>Sidebar [menu_section]</td>
            <td>sb_[menu_section]</td>
            <td><?php echo T_('Collapse/Expand Sidebar [menu_section]'); ?></td>
        </tr>
        <tr>
            <td>Sidebar state</td>
            <td>sidebar_state</td>
            <td><?php echo T_('Collapse/Expand Sidebar'); ?></td>
        </tr>
    </tbody>
</table>
