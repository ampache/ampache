<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

?>

<?php $results = 0; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{{ config('app.locale') }}" lang="{{ config('app.locale') }}">
<head>
    <!-- Propulsed by Ampache | ampache.org -->
    <meta http-equiv="Content-Type" content="text/html; Charset={{ config('system.site_charset') }}" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
    <meta http-equiv="pragma" content="no-cache" />

    <title>Ampache :: For the love of Music - Install</title>
	<link rel="stylesheet" href="{{ url('css/jquery-ui.css') }}">
	<link rel="stylesheet" href="{{ url('themes/' . Config::get('theme.theme') . '/css/default.css') }}" type="text/css" media="screen">
	<link rel="stylesheet" href="{{ url('css/base.css') }}" type="text/css" media="screen" />
	<link rel="stylesheet" href="{{ url('css/bootstrap.css') }}" type="text/css" media="screen" />
	<link rel="stylesheet" href="{{ url('themes/reborn/css/light.css') }}" type="text/css" media="screen" />
	<link rel="stylesheet" href="{{ url('css/install-doped.css') }}" type="text/css" media="screen" />
    <script src="{{ url('js/jquery.js') }}" language="javascript" type="text/javascript"></script>
    <script src="{{ url('js/bootstrap.js') }}" language="javascript" type="text/javascript"></script>
   <script src="{{ url('js/base.js') }}" language="javascript" type="text/javascript"></script>
    <script src="{{ url('js/jquery-ui.js') }}"></script>
    <script src="{{ url('js/ajax.js') }}" type="text/javascript"></script>
    <script src="{{ url('js/slideshow.js') }}" type="text/javascript"></script>
    <script src="{{ url('js/jquery.prettyPhoto.js') }}" type="text/javascript"></script>
    <script src="{{ url('js/jquery.validate.js') }}" type="text/javascript"></script>
    <script src="{{ url('js/additional-methods.js') }}" type="text/javascript"></script>

	</head>
	<body>
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">

            <a class="navbar-brand" href="#">
                <img src="{{ url('themes/reborn/images/ampache.png') }}" title="Ampache" alt="Ampache">
                <?php echo T_('Ampache Installation'); ?> - For the love of Music
            </a>
    </div>
    
        <div id="guts">
            @yield('content')
        </div>
            <div style="clear:both;">
            </div>
        @if (session('status'))
        <div class="alert alert-success">
           {{ session('status') }}
        </div>
 	    @endif
	</body>
</html>    