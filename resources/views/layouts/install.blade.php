<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <title>{{ config('app.name', 'Ampache') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="{{ url('favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/w3.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('css/install-doped.css') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="{{ asset('themes/reborn/css/light.css') }}">
    <link rel="stylesheet" href="{{ asset('css/parsley.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery.multiselect.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="{{ captcha_layout_stylesheet_url() }}" type="text/css" rel="stylesheet">

{{-- <script src="{{ asset('js/app.js') }}"></script>  --}}
    <script src="{{ asset('js/jquery.js') }}"></script>
    <script src="{{ asset('js/jquery-validate.js') }}"></script>
    <script src="{{ asset('js/jquery-ui.js') }}"></script>
{{--    <script src="{{ asset('js/dynamicpage.js') }}"></script> --}}
    <script src="{{ asset('js/base.js') }}"></script>
    <script src="{{ asset('js/moment.js') }}"></script>
    <script src="{{ asset('js/jquery.multiselect.js') }}"></script>
{{--    <script src="{{ asset('js/parsley.js') }}"></script> --}}

 </head>
<body class="w3-laravel-body">
     <div class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="themes/reborn/images/ampache.png" title="Ampache" alt="Ampache">
                <?php echo __('Ampache Installation'); ?> - For the love of Music
            </a>
        </div>
    </div>

	<div id="main-content" class="container" role="main">
		@yield('content')
	</div>
</body>
</html>
