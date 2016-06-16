<h1 id="headerlogo">
  <a href="{{ url('/') }}">
    <img src="{{ url('/images/ampache.png') }}" title="{{ Config::get('theme.title') }}" alt="{{ Config::get('theme.title') }}" />
  </a>
</h1>

<div id="headerbox">
    <div class="box box_headerbox">
        <div class="box-inside">
            @include('includes.search_bar')
            @include('includes.playtype_switch')
        </div>
    </div>
    @if (Auth::check())
        <span id="loginInfo">
            <a href="{{ url('user/' . Auth::user()->id) }}">{{ Auth::user()->name ?: Auth::user()->username }}</a>
            @if (Config::get('feature.sociable'))
            <a href="{!! url('message') !!}" title="{{ T_('New Messages') }}">({{ count(PrivateMsg::get_private_msgs(Auth::user()->id, true)) }})</a>
            @endif
            <a rel="nohtml" href="{!! url('logout') !!}">[{{ T_('Logout') }}]</a>
        </span>
    @else
        <span id="loginInfo">
            <a href="{!! url('auth/login') !!}" rel="nohtml">{{ T_('Login') }}</a>
            @if (Config::get('user.allow_public_registration'))
                / <a href="{!! url('auth/register') !!}" rel="nohtml">{{ T_('Register') }}</a>
            @endif
        </span>
    @endif
</div>