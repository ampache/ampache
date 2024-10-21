var lastaction = new Date().getTime();
var refresh_slideshow_interval = jsPrefExistsFlickrApiKey ? jsAmpConfigSlideshowTime : 0;
var iSlideshow = null;
var tSlideshow = null;

$("#aslideshow").click(function(e) {
    if (!$(e.target).hasClass('rhino-btn')) {
        update_action();
    }
});

export function init_slideshow_check()
{
    if (refresh_slideshow_interval > 0) {
        if (tSlideshow != null) {
            clearTimeout(tSlideshow);
        }
        tSlideshow = window.setTimeout(function(){init_slideshow_refresh();}, refresh_slideshow_interval * 1000);
    }
}
export function swap_slideshow()
{
    if (iSlideshow == null) {
        init_slideshow_refresh();
    } else {
        stop_slideshow();
    }
}
export function init_slideshow_refresh()
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
export function refresh_slideshow()
{
    if (iSlideshow != null) {
        ajaxPut(jsAjaxUrl + "?page=index&action=slideshow", '');
    } else {
        init_slideshow_check();
    }
}
export function stop_slideshow()
{
    if (iSlideshow != null) {
        iSlideshow = null;
        $("#aslideshow").css({'display': 'none'});
    }
}
export function update_action()
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