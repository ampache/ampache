function loadContentData(data, status, jqXHR)
{
    var $response = $(data);

	if ($response.find("#guts").length === 0) {
		$("body").undelegate("a");
		$("body").undelegate("form");
		$("body").empty().append($response);
	} else {
		var $mainContent = $("#content");
		var $pageWrap    = $("#guts");
		$mainContent.empty().append($response.find("#guts"));
		$mainContent.fadeIn(200, function() {
			$pageWrap.animate({
				height: $mainContent.height() + "px"
			});
		});
		$("a[rel^='prettyPhoto']").prettyPhoto({social_tools:false});
		initTabs();
	}
}

function loadContentPage(url)
{
    var $mainContent = $("#content");

    $mainContent
        .find("#guts")
        .fadeOut(200, function() {
            $.get(url, function (data, status, jqXHR) {
                loadContentData(data, status, jqXHR);
            }, "html");
        });
}

var sseSource = null;
function sse_worker(url) {
	if(typeof(EventSource) !== "undefined") {
		sseSource = new EventSource(url);
		sseSource.onmessage = function(event) {
			eval(event.data);
		};
		sseSource.onopen = function() {
			displayNotification("Connected through Server-Sent Events, processing...", 5000);
		};
		sseSource.onerror = function() {
			displayNotification("Server-Sent Events connection error. Re-connection...", 5000);
		};
	} else {
		// Server-Sent Events not supported, call the update in ajax and the output result
		$.get(url + "&html=1", function (data) {
			$("#guts").append(data);
		}, "html")
	}
}

function stop_sse_worker() {
	if (sseSource !== null) {
		sseSource.close();
		sseSource = null;
	}
}

function display_sse_error(error) {
	displayNotification("ERROR: " + error, 10000);
}

$(function() {

    var newHash      = "";

    $("body").delegate("a", "click", function() {
        var link = $(this).attr("href");
        if (link !== undefined && link != "" && link.indexOf("javascript:") != 0 && link != "#" && link != undefined && $(this).attr("onclick") == undefined && $(this).attr("rel") != "nohtml" && $(this).attr("target") != "_blank") {
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
        // We do not support ajax post with files or login form, neither specific target
        var $file = $(this).find("input[type=file]");
        if ($(this).attr("name") !== "login" && $(this).attr("name") !== "export" && (!$file || !$file.val() || $file.val() === "") && ($(this).attr("target") === undefined || $(this).attr("target") === "")) {
            var postData = $(this).serializeArray();
            var formURL = $(this).attr("action");

			if (formURL.indexOf("javascript:") !== 0) {
				$.ajax(
				{
					url : formURL,
					type: "POST",
					data : postData,
					success:function(data, status, jqXHR)
					{
						loadContentData(data, status, jqXHR);
						window.location.hash = "";
					},
					error: function(jqXHR, status, errorThrown)
					{
						alert(errorThrown);
					}
				});

				e.preventDefault();
			}
        }
    });

    $(window).bind("hashchange", function(){
        newHash = window.location.hash.substring(1);
        if (newHash && newHash.indexOf("prettyPhoto") != 0 && newHash.indexOf(".php") > -1) {
            loadContentPage(jsWebPath + "/" + newHash);
            return false;
        };
    });

    $(window).trigger("hashchange");

});
