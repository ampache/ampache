<!doctype html>
<html>
<head>
    @include('includes.head')
</head>
<body>
<div id="aslideshow">
    <div id="aslideshow_container">
        <div id="fslider"></div>
        <div id="fslider_script"></div>
    </div>
</div>
<script type="text/javascript" language="javascript">
    $("#aslideshow").click(function(e) {
        if (!$(e.target).hasClass('rhino-btn')) {
            update_action();
        }
    });
</script>

<?php if (Config::get('theme.cookie_disclaimer') && !isset($_COOKIE['cookie_disclaimer'])) {
?>
<script type="text/javascript" language="javascript">
noty({text: '<?php printf(json_encode(nl2br(/* HINT: Translator, "%s" is replaced by "cookie settings" */T_("We have placed cookies on your computer to help make this website better. You can change your %s at any time.\nOtherwise, we will assume you are OK to continue.\n\nClick on this message to not display it again."))),
            "<a href=\"" . url('cookie_disclaimer.php') . "\">" . T_('cookie settings') . "</a>");
?>',
        type: 'warning',
        layout: 'bottom',
        timeout: false,
        callback: {
            afterClose: function() {
                $.cookie('cookie_disclaimer', '1', { expires: 365 });
            }
        },
    });
</script>
<?php 
} ?>

@if (Config::get('feature.libitem_contextmenu'))
<script type="text/javascript" language="javascript">
    function libitem_action(item, action)
    {
        var iinfo = item.attr('id').split('_', 2);
        var object_type = iinfo[0];
        var object_id = iinfo[1];

        if (action !== undefined && action !== '') {
            ajaxPut(jsAjaxUrl + action + '&object_type=' + object_type + '&object_id=' + object_id);
        } else {
            showPlaylistDialog(this, object_type, object_id);
        }
    }

    $.contextMenu({
        selector: ".libitem_menu",
        items: {
            play: {name: "{{ trans('play') }}", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay'); }},
            play_next: {name: "{{ trans('playnext') }}", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay&playnext=true'); }},
            play_last: {name: "{{ trans('playlast') }}", callback: function(key, opt){ libitem_action(opt.$trigger, '?page=stream&action=directplay&append=true'); }},
            add_tmp_playlist: {name: "{{ trans('basket') }}", callback: function(key, opt){ libitem_action(opt.$trigger, '?action=basket'); }},
            add_playlist: {name: "{{ trans('basketexist') }}", callback: function(key, opt){ libitem_action(opt.$trigger, ''); }}
        }
    });
</script>
@endif

<!-- rfc3514 implementation -->
<div id="rfc3514" style="display:none;">0x0</div>
<div id="notification" class="notification-out"><img src="{{ url('images/icon_info.png') }}/><span id="notification-content"></span></div>
<div id="maincontainer">
    
    <div id="header">
        @include('includes.header')
    </div>
    
    <div id="sidebar" class="sidebar-fixed">
        @include('includes.sidebar')
    </div>

    <div id="rightbar">
        @include('includes.rightbar')
    </div>

    <!-- Tiny little div, used to cheat the system -->
    <div id="ajax-loading">Loading . . .</div>
    <div id="util_div" style="display:none;"></div>
    <iframe name="util_iframe" id="util_iframe" style="display:none;" src="{{ url('util.php') }}"></iframe>
    <div id="content">
        <div id="guts">
            @yield('content')
        </div>
            <div style="clear:both;">
            </div>
    </div>

    <footer id="footer" class="row">
        @include('includes.footer')
    </footer>

</div>
</body>
</html>