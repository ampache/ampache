$('#sidebar-header').click(function(){
    if (jsAmpConfigSidebarHideSwitcher) return false;

    var newstate = "collapsed";
    if ($('#sidebar-header').hasClass("sidebar-header-collapsed")) {
        newstate = "expanded";
    }

    if (newstate != "expanded") {
        $("#content").addClass("content-left-wild", 600);
    } else {
        $("#content").removeClass("content-left-wild", 1000);
    }

    $('#sidebar').hide(500, function() {
        if (newstate == "expanded") {
            $('#sidebar-content-light').removeClass("sidebar-content-light-collapsed");
            $('#sidebar-content').removeClass("sidebar-content-collapsed");
            $('#sidebar-header').removeClass("sidebar-header-collapsed");
        } else {
            $('#sidebar-content').addClass("sidebar-content-collapsed");
            $('#sidebar-header').addClass("sidebar-header-collapsed");
            $('#sidebar-content-light').addClass("sidebar-content-light-collapsed");
        }

        $('#sidebar').show(500);
    });

    Cookies.set('sidebar_state', newstate, {jsCookieString});
});