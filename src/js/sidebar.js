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

// RIGHTBAR
export function ToggleRightbarVisibility()
{
    if ($("#rightbar").is(":visible")) {
        $("#rightbar").slideUp();
    } else {
        $("#rightbar").slideDown();
    }
}

// kick off toggling the rightbar when it is loaded
export function RightbarInit() {
    if (jsBasketCount > 0 || jsAmpConfigPlayType === "localplay") {
        $("#content").removeClass("content-right-wild", 500);
        $("#footer").removeClass("footer-wild", 500);
        $("#rightbar").removeClass("hidden");
        $("#rightbar").show("slow");
    } else {
        $("#content").addClass("content-right-wild", 500);
        $("#footer").addClass("footer-wild", 500);
        $("#rightbar").hide("slow");
    }
}
