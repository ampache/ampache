<div id="sidebar-header"></div>
<div id="sidebar-content" >
    <ul id="sidebar-tabs">
        @if (Auth::check())
            <li id='sb_tab_home' class='sb1'>
                {!! Ajax::button('index/sidebar/home', 'home', T_('Home'), 'sidebar_home') !!}
                @if (UI::get_current_sidebar_tab() === 'home')
                    <div id="sidebar-page" class="sidebar-page-float">
                        @include('includes.sidebar_home')
                    </div>
                @endif
            </li>
            @if (Config::get('feature.allow_localplay_playback'))
                <li id='sb_tab_localplay' class='sb1'>
                    {!! Ajax::button('index/sidebar/localplay', 'volumeup', T_('Localplay'), 'sidebar_localplay') !!}
                    @if (UI::get_current_sidebar_tab() === 'localplay')
                        <div id="sidebar-page" class="sidebar-page-float">
                            @include('includes.sidebar_localplay')
                        </div>
                    @endif
                </li>
            @endif
            <li id='sb_tab_preferences' class='sb1'>
                {!! Ajax::button('index/sidebar/preferences', 'preferences', T_('Preferences'), 'sidebar_preferences') !!}
                @if (UI::get_current_sidebar_tab() === 'preferences')
                    <div id="sidebar-page" class="sidebar-page-float">
                        @include('includes.sidebar_preferences')
                    </div>
                @endif
            </li>
            @if (Auth::user()->isAdmin())
                <li id='sb_tab_modules' class='sb1'>
                    {!! Ajax::button('index/sidebar/modules', 'plugin', T_('Modules'), 'sidebar_modules') !!}
                    @if (UI::get_current_sidebar_tab() === 'modules')
                        <div id="sidebar-page" class="sidebar-page-float">
                            @include('includes.sidebar_modules')
                        </div>
                    @endif
                </li>
                <li id='sb_tab_admin' class='sb1'>
                    {!! Ajax::button('index/sidebar/admin', 'admin', T_('Admin'), 'sidebar_admin') !!}
                    @if (UI::get_current_sidebar_tab() === 'admin')
                        <div id="sidebar-page" class="sidebar-page-float">
                            @include('includes.sidebar_admin')
                        </div>
                    @endif
                </li>
            @endif

            <li id="sb_tab_logout" class="sb1">
                <a target="_top" href="{{ url('logout') }}" id="sidebar_logout" rel="nohtml" >
                    <img src="{{ url_icon('logout') }}" alt="{{ T_('Logout') }}" />
                </a>
            </li>
        @else
            <li id="sb_tab_home" class="sb1">
                <div id="sidebar-page" class="sidebar-page-float">
                    @include('includes.sidebar_home')
                </div>
            </li>
        @endif
    </ul>

    <script type="text/javascript">
    $(function() {
        $(".header").click(function () {

            $header = $(this);
            //getting the next element
            $content = $header.next();
            //open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
            $content.slideToggle(500, function() {
                $header.children(".header-img").toggleClass("expanded collapsed");
                var sbstate = "expanded";
                if ($header.children(".header-img").hasClass("collapsed")) {
                    sbstate = "collapsed";
                }
                $.cookie('sb_' + $header.children(".header-img").attr('id'), sbstate, { expires: 30, path: '/'});
            });

        });
    });

    $(document).ready(function() {
        // Get a string of all the cookies.
        var cookieArray = document.cookie.split(";");
        var result = new Array();
        // Create a key/value array with the individual cookies.
        for (var elem in cookieArray) {
            var temp = cookieArray[elem].split("=");
            // We need to trim whitespaces.
            temp[0] = $.trim(temp[0]);
            temp[1] = $.trim(temp[1]);
            // Only take sb_* cookies (= sidebar cookies)
            if (temp[0].substring(0, 3) == "sb_") {
                result[temp[0].substring(3)] = temp[1];
            }
        }
        // Finds the elements and if the cookie is collapsed, it
        // collapsed the found element.
        for (var key in result) {
            if ($("#" + key).length && result[key] == "collapsed") {
                $("#" + key).removeClass("expanded");
                $("#" + key).addClass("collapsed");
                $("#" + key).parent().next().slideToggle(0);
            }
        }
    });
    </script>
</div>