@extends('layouts.fullform')

@section('content')

        <div class="page-header requirements">
            <h1><?php echo T_('Requirements'); ?></h1>
        </div>
        <div class="well">
            <p>
                <?php echo T_('This page handles the installation of the Ampache database and the creation of the ampache.cfg.php file. Before you continue please make sure that you have the following prerequisites:'); ?>
            </p>
            <ul>
                <li><?php echo T_('A MySQL server with a username and password that can create/modify databases'); ?></li>
            </ul>
        </div>
<table class="table" cellspacing="0" cellpadding="0">
    <tr>
        <th><?php echo T_('CHECK'); ?></th>
        <th><?php echo T_('STATUS'); ?></th>
        <th><?php echo T_('DESCRIPTION'); ?></th>
    </tr>
    <?php // require $prefix . '/templates/show_test_table.inc.php'; ?>
    <tr>
        <td><?php echo sprintf(T_('%s is readable'), 'ampache.cfg.php.dist'); ?></td>
        <td><?php echo T_('This tests whether the configuration template can be read.'); ?></td>
    </tr>
    <tr>
        <td><?php echo sprintf(T_('%s is readable'), 'ampache.sql'); ?></td>
        <td><?php echo T_('This tests whether the file needed to initialise the database structure is available.'); ?></td>
    </tr>
    <tr>
        <td><?php echo T_('ampache.cfg.php is writable'); ?></td>
        <td><?php echo T_('This tests whether PHP can write to config/. This is not strictly necessary, but will help streamline the installation process.'); ?></td>
    </tr>
</table>
@endsection