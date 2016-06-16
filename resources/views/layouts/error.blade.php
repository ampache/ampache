<!DOCTYPE html>
<html
<head>
    <!-- Propulsed by Ampache | ampache.org -->
    <meta http-equiv="refresh" content="10;URL=<?php echo($redirect_url);?>" />
    <title><?php echo( T_("Ampache error page"));?></title>
    <link href="lib/components/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="lib/components/bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
    <link rel="stylesheet" href="templates/install-doped.css" type="text/css" media="screen" />
</head>
<body>
    <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="{{ App::staticUrl('public/img/ampache.png') }}" title="Ampache" alt="Ampache">
                Ampache - For the love of Music
            </a>
        </div>
    </div>
    <div class="container" role="main">
        <div class="jumbotron">
            <h1>{{ lang('error') }}</h1>
            <p>{{ lang('error_redirect') }}<?php echo (T_("The folowing error has occured, you will automaticly be redirected after 10 seconds.") ); ?></p>
        </div>
        <h2>{{ lang('error_messages') }}:</h2>
        @yield('content')
    </div>
</body>
</html>
