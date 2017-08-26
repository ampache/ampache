<!doctype html>
<html>
<head>

@include('includes.head')
</head>
<body id="registerPage">
    <div id="maincontainer">
        <div id="header">
            <a rel="nohtml" href="{!! url('/') !!}"><h2 id="headerlogo"></h2></a>
            <span>@yield('title')</span>
        </div>
        <div id="registerbox">
            @yield('content')
        </div>
    </div>
    <footer id="footer" class="row">
        @include('includes.footer')
    </footer>
</body>
</html>