<h1 id="headerlogo">
  <a href="{{ url('/') }}">
    <img src="{{ url('/images/ampache.png') }}" title="{{ Config::get('theme.title') }}" alt="{{ Config::get('theme.title') }}" />
  </a>
</h1>

<div id="headerbox">
    <div class="box box_headerbox">
        <div class="box-inside">
            @include('includes.search_bar')
{{--            @include('includes.playtype_switch') --}}
        </div>
    </div>
    @if (Auth::check())
        <span id="loginInfo">
            <a href="{{ url('user/' . Auth::user()->id) }}">{{ Auth::user()->name ?: Auth::user()->username }}</a>
           @if (Config::get('feature.sociable'))
               <a href="{{ url('/messages/index', [Auth::user()->id]) }}">({{ App\Models\Private_Msg::newMessageCount(Auth::user()->id) }} messages)</a>
            @endif
             <a href="{!! url('logout') !!}">[{{ T_('Logout') }}]</a>
        </span>
    @else
        <span id="loginInfo">
            <a href="{!! url('/login') !!}" rel="nohtml">{{ T_('Login') }}</a>
            @if (Config::get('user.allow_public_registration'))
                / <a href="{!! url('register') !!}" rel="nohtml">{{ T_('Register') }}</a>
            @endif
        </span>
    @endif
</div>
<script>
function showMessages( id) {
	var url = "{{ url('/messages/index') }}";
//	$("#guts").html("");
	$('#guts').text('');
	$("#guts").load(url);
	}
</script>
