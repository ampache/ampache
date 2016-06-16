var lastaction = new Date().getTime();
var refresh_slideshow_interval = 10;
var iSlideshow = null;
var tSlideshow = null;
function init_slideshow_check()
{
    if (refresh_slideshow_interval > 0) {
        if (tSlideshow != null) {
            clearTimeout(tSlideshow);
        }
        tSlideshow = window.setTimeout(function(){init_slideshow_refresh();}, refresh_slideshow_interval * 1000);
    }
}
function swap_slideshow()
{
    if (iSlideshow == null) {
        init_slideshow_refresh();
    } else {
        stop_slideshow();
    }
}
function init_slideshow_refresh()
{
    if ($("#webplayer").is(":visible")) {
        clearTimeout(tSlideshow);
        tSlideshow = null;

        $("#aslideshow").height($(document).height())
          .css({'display': 'inline'});

        iSlideshow = true;
        refresh_slideshow();
    }
}
function refresh_slideshow()
{
    if (iSlideshow != null) {
        ajaxPut(jsAjaxUrl + '?page=index&action=slideshow', '');
    } else {
        init_slideshow_check();
    }
}
function stop_slideshow()
{
    if (iSlideshow != null) {
        iSlideshow = null;
        $("#aslideshow").css({'display': 'none'});
    }
}
function update_action()
{
    lastaction = new Date().getTime();
    stop_slideshow();
    init_slideshow_check();
}
$(document).mousemove(function(e) {
    if (iSlideshow == null) {
        update_action();
    }
});
$(document).ready(function() {
    init_slideshow_check();
});