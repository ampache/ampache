<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ url('/images/ampache.png') }}" type="image/png">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css"> 
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="{{ url('css/dark.css') }}">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
    <script src="{{ asset('js/jquery-validate.js') }}"></script>
    <title>{{ config('app.name', 'Ampache') }}</title>

    <!-- Styles -->
<!--     <link href="{{ asset('css/default.css') }}" rel="stylesheet"> -->
<!--     <link href="{{ asset('css/app.css') }}" rel="stylesheet"> -->
    
</head>
<body class="w3-dark-body w3-padding-48">

      @section('header')
             @include('partials.header')
      @show

	  @section('sidebar')
		Reserved for including the sidebar.
		@include('partials.sidebar.main')
	  @show
	  
	<div id="main-content" class="container">
		@yield('content')
	</div>
    
</body>
</html>
