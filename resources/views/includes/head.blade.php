<!-- Propulsed by Ampache | ampache.org -->
<link rel="search" type="application/opensearchdescription+xml" title="{{ Config::get('theme.title') }}?>" href="{{ url('search.php?action=descriptor') }}" />
@if (Config::get('feature.use_rss'))
    <link rel="alternate" type="application/rss+xml" title="{{ T_('Now Playing') }}" href="{{ url('rss/now_playing') }}" />
    <link rel="alternate" type="application/rss+xml" title="{{ T_('Recently Played') }}" href="{{ url('rss/recently_played') }}" />
    <link rel="alternate" type="application/rss+xml" title="{{ T_('Newest Albums') }}" href="{{ url('rss/latest_album') }}" />
    <link rel="alternate" type="application/rss+xml" title="{{ T_('Newest Artists') }}" href="{{ url('rss/latest_artist') }}" />
    @if (Config::get('feature.sociable'))
        <link rel="alternate" type="application/rss+xml" title="{{ T_('Newest Shouts') }}" href="{{ url('rss/latest_shout') }}" />
    @endif
@endif
<meta http-equiv="Content-Type" content="application/xhtml+xml; charset={{ Config::get('system.site_charset') }}" />
<title>{{ Config::get('theme.title') }}</title>

<link rel="stylesheet" href="{{ url(elixir('css/app.css')) }}" type="text/css" media="screen" />
<link rel="stylesheet" href="{{ url(elixir('css/vendors.css')) }}" type="text/css" media="screen" />
<link rel="stylesheet" href="{{ url(elixir('css/main.css')) }}" type="text/css" media="screen" />
<link rel="stylesheet" href="{{ url('themes/' . Config::get('theme.theme') . '/css/default.css') }}" type="text/css" media="screen" />
<link rel="stylesheet" href="{{ url('themes/' . Config::get('theme.theme') . '/css/' . Config::get('theme.color') . '.css') }}" type="text/css" media="screen" />
<script src="{{ url(elixir('js/vendors.js')) }}" language="javascript" type="text/javascript"></script>
<script src="{{ url(elixir('js/main.js')) }}" language="javascript" type="text/javascript"></script>

<script type="text/javascript" charset="utf-8">
    $(document).ready(function(){
        $("a[rel^='prettyPhoto']").prettyPhoto({social_tools:false});
        @if (Config::get('feature.geolocate'))
            geolocate_user();
        @endif
    });

    // Using the following workaround to set global variable available from any javascript script.
    var jsAjaxUrl = "{{ url ('server/ajax.server.php') }}";
    var jsWebPath = "{{ url('') }}";
    var jsAjaxServer = "{{ url ('server') }}";
    var jsSaveTitle = "{{ T_('Save') }}";
    var jsCancelTitle = "{{ T_('Cancel') }}";
</script>