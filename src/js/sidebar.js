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

$(function() {
    $(".header").click(function () {
        var $header = $(this);
        // getting the next element
        var $content = $header.next();
        // open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
        $content.slideToggle(500, function() {
            $header.children(".header-img").toggleClass("expanded collapsed");
            var sbstate = "expanded";
            if ($header.children(".header-img").hasClass("collapsed")) {
                sbstate = "collapsed";
            }
            Cookies.set('sb_' + $header.children(".header-img").attr('id'), sbstate, {jsCookieString});
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
    // Finds the elements and if the cookie is collapsed, it collapsed the found element.
    for (var key in result) {
        if ($("#" + key).length && result[key] == "collapsed") {
            $("#" + key).removeClass("expanded");
            $("#" + key).addClass("collapsed");
            $("#" + key).parent().next().slideToggle(0);
        }
    }
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
