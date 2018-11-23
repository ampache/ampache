<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <title>{{ config('app.name', 'Ampache') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" type="image/png" href="{{ url('favicon.png') }}">
    <link rel="stylesheet" href="{{ url('css/w3.css') }}">
    <link rel="stylesheet" href="{{ url('css/app.css') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="{{ url('css/dark.css') }}">
    <link rel="stylesheet" href="{{ url('css/parsley.css') }}">
    <link rel="stylesheet" href="{{ url('css/jquery-ui.css') }}">
    <link rel="stylesheet" href="{{ url('css/jquery.multiselect.css') }}">
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
    <script src="{{ asset('js/parsley.js') }}"></script>

 </head>
<body class="w3-laravel-body w3-padding-48"> 
      @section('header')
             @include('partials.header')
      @show
	  @section('sidebar')
		Reserved for including the sidebar.
		@include('partials.sidebar.main', ['roles' => $roles])
	  @show
	  
	<div id="main-content" class="w3-container" style="margin-left:11%">
		@yield('content')
	</div>
</body>
</html>
