

@section('header')

<div class="w3-bar w3-black w3-border-0 w3-top" style="height:48px;">
  <div class="w3-bar-item" style=" padding: 0px 24px;">
  <a href="{{ url('/') }}">
    <img width="48" height="48" src="{{ url('/images/ampache.png') }}" title="{{ Config::get('theme.title') }}" alt="{{ Config::get('theme.title') }}" />
  </a>
</div>
  <div class="w3-bar-item">
      @if (Auth::check())
        <span id="loginInfo">
            <a
                onclick="dialogEdit('','', '{{ url('user/') . Auth::user()->id }}', 'user-login')"                      
               href="#">{{ Auth::user()->name ?: Auth::user()->username }}</a>
           @if (Config::get('feature.sociable'))
              <a href="{{ url('/messages/index', [Auth::user()->id]) }}">({{ App\Models\PrivateMsg::newMessageCount(Auth::user()->id) }} messages)</a>
            @endif
             <a href="{!! route('logout') !!}"
				 loadTab("home");
				 onclick="event.preventDefault();
                 document.getElementById('logout-form').submit();">
                 Logout
             </a>
             <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                {{ csrf_field() }}
             </form>
        </span>
    @else
        <span id="loginInfo" class="w3-text-orange">
            <a 
               onclick="dialogEdit('','', '{!! url('/login') !!}', 'user-login')"           
            href="#" rel="nohtml">{{ 'Login' }}</a>
            @if (Config::get('user.allow_public_registration'))
                / <a
                   onclick="dialogEdit('','', '{!! url('register') !!}', 'user-register')" 
                   href="#" rel="nohtml">{{ 'Register' }}</a>
            @endif
        </span>
    @endif
  </div>
  <div class="w3-bar-item w3-right ">
  	@include('partials.search_bar')
  </div>
</div>
@endsection