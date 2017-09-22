@extends('layouts.installer')

@section('container')

        <div class="page-header requirements">
            <h1><?php echo T_('Requirements'); ?></h1>
        </div>
        <div class="well">
            <p>
                <?php echo T_("The following pages handle the installation of the Ampache database and validates some required dependencies." .
                    "  Before you continue please make sure that you have the following prerequisites:"); ?>
            </p>
            <ul>
                <li>A MySQL server with a username and password that can create/modify databases</li>
            </ul>
        </div>
<table class="table" cellspacing="0" cellpadding="0">
    <tr>
        <th><?php echo T_('CHECK'); ?></th>
        <th><?php echo T_('STATUS'); ?></th>
        <th><?php echo T_('DESCRIPTION'); ?></th>
    </tr>
            <?php // require resource_path('views/includes/test_table.blade.php');?>
    
<tr>
    <td valign="top"><?php echo T_('PHP hash extension'); ?></td>
    <td valign="top">
    <?php echo debug_result(check_php_hash()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether you have the hash extension enabled. This extension is required by Ampache.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('SHA256'); ?></td>
    <td valign="top">
    <?php echo debug_result(check_php_hash_algo()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether the hash extension supports SHA256. This algorithm is required by Ampache.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP PDO extension'); ?></td>
    <td valign="top">
    <?php echo debug_result(check_php_pdo()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether you have the PDO extension enabled. This extension is required by Ampache.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('MySQL'); ?></td>
    <td valign="top">
    <?php echo debug_result(check_php_pdo_mysql()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether the MySQL driver for PDO is enabled. This driver is required by Ampache.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP session extension'); ?></td>
    <td valign="top">
    <?php echo debug_result(check_php_session()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether you have the session extension enabled. This extension is required by Ampache.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP iconv extension'); ?></td>
    <td valign="top">
    <?php echo debug_result(App\Support\UI::check_iconv()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether you have the iconv extension enabled. This extension is required by Ampache.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP JSON extension'); ?></td>
    <td valign="top">
    <?php echo debug_result(check_php_json()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether you have the JSON extension enabled. This extension is required by Ampache.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP curl extension'); ?></td>
    <td valign="top">
    <?php echo debug_wresult(check_php_curl()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether you have the curl extension enabled. This is not strictly necessary, but may result in a better experience.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP zlib extension'); ?></td>
    <td valign="top">
    <?php echo debug_wresult(check_php_zlib()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether you have the zlib extension enabled. This is not strictly necessary, but may result in a better experience (zip download).'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP simplexml extension'); ?></td>
    <td valign="top">
    <?php echo debug_wresult(check_php_simplexml()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether you have the simplexml extension enabled. This is not strictly necessary, but may result in a better experience.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP GD extension'); ?></td>
    <td valign="top">
    <?php echo debug_wresult(check_php_gd()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether you have the GD extension enabled. This is not strictly necessary, but may result in a better experience.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP safe mode disabled'); ?></td>
    <td valign="top">
    <?php echo debug_result(check_php_safemode()); ?>
    </td>
    <td>
    <?php echo T_('This test makes sure that PHP is not running in safe mode. Some features of Ampache will not work correctly in safe mode.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP memory limit override'); ?></td>
    <td valign="top">
    <?php echo debug_wresult(check_override_memory()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether Ampache can override the memory limit. This is not strictly necessary, but may result in a better experience.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP execution time override'); ?></td>
    <td valign="top">
    <?php echo debug_wresult(check_override_exec_time()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether Ampache can override the limit on maximum execution time. This is not strictly necessary, but may result in a better experience.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP max upload size'); ?></td>
    <td valign="top">
    <?php echo debug_wresult(check_upload_size()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether Ampache can upload medium files (>= 20M). This is not strictly necessary, but may result in a better experience.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP Integer Size'); ?></td>
    <td valign="top">
    <?php echo debug_wresult(check_php_int_size()); ?>
    </td>
    <td>
    <?php echo T_('This tests whether Ampache can manage large files (> 2GB). This is not strictly necessary, but may result in a better experience. This generally requires 64-bit operating system.'); ?>
    </td>
</tr>
<tr>
    <td valign="top"><?php echo T_('PHP mbstring.func_overload'); ?></td>
    <td valign="top">
    <?php echo debug_result(check_mbstring_func_overload()); ?>
    </td>
    <td>
    <?php printf(T_('This tests whether PHP %s is set as it may break the ID3 tag support. This is not stricly necessary, but enabling Ampache ID3 tag write support (disabled by default) along with mbstring.func_overload may result in irreversible corruption of your music files.'), '<a href="http://php.net/manual/en/mbstring.overload.php">mbstring.func_overload</a>'); ?>
    </td>
</tr>
</table>
    <a href="{{ url('/install/show_db') }} " class="btn btn-md btn-warning">Continue</a>
@endsection