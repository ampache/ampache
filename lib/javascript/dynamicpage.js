function loadContentData(data, status, jqXHR)
{
    var $mainContent = $("#content");
    var $pageWrap    = $("#guts");
    var $response = $(data);
    
    $mainContent.empty().append($response.find("#guts"));
    $mainContent.fadeIn(200, function() {
        $pageWrap.animate({
            height: $mainContent.height() + "px"
        });
    });
    $("a[rel^='prettyPhoto']").prettyPhoto({social_tools:false});
    initTabs();
}

function loadContentPage(url)
{
    var $mainContent = $("#content");

    $mainContent
        .find("#guts")
        .fadeOut(200, function() {
            $.get(url, function (data, status, jqXHR) {
                loadContentData(data, status, jqXHR);
            }, 'html');
        });
}

$(function() {

    var newHash      = "";
    
    $("body").delegate("a", "click", function() {
        var link = $(this).attr("href");
        if (link != "" && link.indexOf("javascript:") != 0 && link != "#" && link != undefined && $(this).attr("onclick") == undefined && $(this).attr("rel") != "nohtml" && $(this).attr("target") != "_blank") {
            if ($(this).attr("rel") != "prettyPhoto") {
                // Ajax load Ampache pages only
                if (link.indexOf(jsWebPath) > -1) {
                    window.location.hash = link.substring(jsWebPath.length + 1);
                    return false;
                }
            } else {
                window.location.hash = $(this).attr("rel");
                return false;
            }
        }
    });
    
    $("body").delegate("form", "submit", function(e) {
        // We do not support ajax post with files
        var $file = $(this).find("input[type=file]");
        if (!$file || !$file.val() || $file.val() == "") {
            var postData = $(this).serializeArray();
            var formURL = $(this).attr("action");

            $.ajax(
            {
                url : formURL,
                type: "POST",
                data : postData,
                success:function(data, status, jqXHR)
                {
                    loadContentData(data, status, jqXHR);
                },
                error: function(jqXHR, status, errorThrown)
                {
                    // Display error here?
                }
            });

            e.preventDefault();
        }
    });
    
    $(window).bind('hashchange', function(){
        newHash = window.location.hash.substring(1);
        if (newHash && newHash.indexOf("prettyPhoto") != 0 && newHash.indexOf(".php") > -1) {
            loadContentPage(jsWebPath + '/' + newHash);
            return false;
        };
    });
    
    $(window).trigger('hashchange');

});