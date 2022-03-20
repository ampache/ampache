/**
  * Rhinoslider 1.05
  * http://rhinoslider.com/
  *
  * Copyright 2014: Sebastian Pontow, Rene Maas (http://renemaas.de/)
  * Dual licensed under the MIT or GPL Version 2 licenses.
  * http://rhinoslider.com/license/
  */
(function ($, window) {

    $.extend($.easing, {
        def: "out",
        out(none, currentTime, startValue, endValue, totalTime) {
            return -endValue * (currentTime /= totalTime) * (currentTime - 2) + startValue;
        },
        kick(none, currentTime, startValue, endValue, totalTime) {
            if ((currentTime /= totalTime / 2) < 1) {
                return endValue / 2 * Math.pow(2, 10 * (currentTime - 1)) + startValue;
            }
            return endValue / 2 * (-Math.pow(2, -10 * --currentTime) + 2) + startValue;
        },
        shuffle(none, currentTime, startValue, endValue, totalTime) {
            if ((currentTime /= totalTime / 2) < 1) {
                return endValue / 2 * currentTime * currentTime * currentTime * currentTime * currentTime + startValue;
            }
            return endValue / 2 * ((currentTime -= 2) * currentTime * currentTime * currentTime * currentTime + 2) + startValue;
        }
    });

    var rhinoSlider = function (element, opts) {
        var
            settings = $.extend({}, $.fn.rhinoslider.defaults, opts),
            $slider = $(element),
            effects = $.fn.rhinoslider.effects,
            preparations = $.fn.rhinoslider.preparations,
            //internal variables
            vars = {
                isPlaying: false,
                intervalAutoPlay: false,
                active: "",
                next: "",
                container: "",
                items: "",
                buttons: [],
                prefix: "rhino-",
                playedArray: [],
                playedCounter: 0,
                original: element
            };

        settings.callBeforeInit();

        var
            setUpSettings = function (settings) {
                settings.controlsPrevNext = String(settings.controlsPrevNext) === "true" ? true : false;
                settings.controlsKeyboard = String(settings.controlsKeyboard) === "true" ? true : false;
                settings.controlsMousewheel = String(settings.controlsMousewheel) === "true" ? true : false;
                settings.controlsPlayPause = String(settings.controlsPlayPause) === "true" ? true : false;
                settings.pauseOnHover = String(settings.pauseOnHover) === "true" ? true : false;
                settings.animateActive = String(settings.animateActive) === "true" ? true : false;
                settings.autoPlay = String(settings.autoPlay) === "true" ? true : false;
                settings.cycled = String(settings.cycled) === "true" ? true : false;
                settings.showTime = parseInt(settings.showTime, 10);
                settings.effectTime = parseInt(settings.effectTime, 10);
                settings.controlFadeTime = parseInt(settings.controlFadeTime, 10);
                settings.captionsFadeTime = parseInt(settings.captionsFadeTime, 10);
                tmpShiftValue = settings.shiftValue;
                tmpParts = settings.parts;
                settings.shiftValue = [];
                settings.parts = [];
                return settings;
            },

            //init function
            init = function ($slider, settings, vars) {
                settings = setUpSettings(settings);

                $slider.wrap("<div class=\"" + vars.prefix + "container\">");
                vars.container = $slider.parent("." + vars.prefix + "container");
                vars.isPlaying = settings.autoPlay;

                //the string, which will contain the button-html-code
                var buttons = "";

                //add prev/next-buttons
                if (settings.controlsPrevNext) {
                    vars.container.addClass(vars.prefix + "controls-prev-next");
                    buttons = "<a class=\"" + vars.prefix + "prev " + vars.prefix + "btn\">" + settings.prevText + "</a><a class=\"" + vars.prefix + "next " + vars.prefix + "btn\">" + settings.nextText + "</a>";
                    vars.container.append(buttons);

                    vars.buttons.prev = vars.container.find("." + vars.prefix + "prev");
                    vars.buttons.next = vars.container.find("." + vars.prefix + "next");

                    //add functionality to the "prev"-button
                    vars.buttons.prev.click(function () {
                        prev($slider, settings);

                        //stop autoplay, if set
                        if (settings.autoPlay) {
                            pause();
                        }
                    });

                    //add functionality to the "next"-button
                    vars.buttons.next.click(function () {
                        next($slider, settings);

                        //stop autoplay, if set
                        if (settings.autoPlay) {
                            pause();
                        }
                    });
                }

                //add play/pause-button
                if (settings.controlsPlayPause) {
                    vars.container.addClass(vars.prefix + "controls-play-pause");
                    buttons = settings.autoPlay ? "<a class=\"" + vars.prefix + "toggle " + vars.prefix + "pause " + vars.prefix + "btn\">" + settings.pauseText + "</a>" : "<a class=\"" + vars.prefix + "toggle " + vars.prefix + "play " + vars.prefix + "btn\">" + settings.playText + "</a>";
                    vars.container.append(buttons);

                    vars.buttons.play = vars.container.find("." + vars.prefix + "toggle");

                    //add functionality
                    vars.buttons.play.click(function () {
                        //self-explaining
                        if (vars.isPlaying === false) {
                            play();
                        } else {
                            pause();
                        }
                    });
                }

                //style
                vars.container.find("." + vars.prefix + "btn").css({
                    position: "absolute",
                    display: "block",
                    cursor: "pointer"
                });

                //hide/show controls on hover or never
                if (settings.showControls !== "always") {
                    var allControls = vars.container.find("." + vars.prefix + "btn");
                    allControls.stop(true, true).fadeOut(0);
                    if (settings.showControls === "hover") {
                        vars.container.mouseenter(function () {
                            allControls.stop(true, true).fadeIn(settings.controlFadeTime);
                        }).mouseleave(function () {
                            allControls.delay(200).fadeOut(settings.controlFadeTime);
                        });
                    }
                }
                if(settings.showControls !== "never"){
                    vars.container.addClass(vars.prefix + "show-controls");
                }

                //get content-elements and set css-reset for positioning
                vars.items = $slider.children();
                vars.items.addClass(vars.prefix + "item");
                vars.items.first().addClass(vars.prefix + "active");

                //give sliderstyle to container
                var sliderStyles = settings.styles.split(","), style;
                $.each(sliderStyles, function(i, cssAttribute){
                    style = $.trim(cssAttribute);
                    vars.container.css(style, $slider.css(style));
                    $slider.css(style, " ");
                    switch(style){
                        case "width":
                        case "height":
                            $slider.css(style, "100%");
                            break;
                    }
                });
                if(vars.container.css("position") === "static"){
                    vars.container.css("position", "relative");
                }

                $slider.css({
                    top: "auto",
                    left: "auto",
                    position: "relative"
                });

                //style items
                vars.items.css({
                    margin: 0,
                    width: $slider.css("width"),
                    height: $slider.css("height"),
                    position: "absolute",
                    top: 0,
                    left: 0,
                    zIndex: 0,
                    opacity: 0,
                    overflow: "hidden"
                });

                vars.items.each(function (i) {
                    $(this).attr("id", vars.prefix + "item" + i);
                });

                //generate navigation
                if (settings.showBullets !== "never") {
                    vars.container.addClass(vars.prefix + "show-bullets");
                    var navi = "<ol class=\"" + vars.prefix + "bullets\">";
                    vars.items.each(function (i) {
                        var $item = $(this);
                        var id = vars.prefix + "item" + i;
                        navi = navi + "<li><a id=\"" + id + "-bullet\" class=\"" + vars.prefix + "bullet\">" + parseInt(i + 1, 10) + "</a></li>";
                    });
                    navi = navi + "</ol>";
                    vars.container.append(navi);

                    vars.navigation = vars.container.find("." + vars.prefix + "bullets");
                    vars.buttons.bullets = vars.navigation.find("." + vars.prefix + "bullet");
                    vars.buttons.bullets.first().addClass(vars.prefix + "active-bullet " + vars.prefix + "first-bullet");
                    vars.buttons.bullets.last().addClass(vars.prefix + "last-bullet");
                    vars.buttons.bullets.click(function () {
                        var itemID = $(this).attr("id").replace("-bullet", "");
                        var $next = vars.container.find("#" + itemID);
                        var curID = parseInt(vars.navigation.find("." + vars.prefix + "active-bullet").attr("id").replace("-bullet", "").replace(vars.prefix + "item", ""), 10);
                        var nextID = parseInt(itemID.replace(vars.prefix + "item", ""), 10);
                        if (curID < nextID) {
                            next($slider, settings, $next);
                        } else if (curID > nextID) {
                            prev($slider, settings, $next);
                        } else {
                            return false;
                        }

                        //stop autoplay, if set
                        if (settings.autoPlay) {
                            pause();
                        }
                    });
                }
                //hide/show bullets on hover or never
                if (settings.showBullets === "hover") {
                    vars.navigation.hide();
                    vars.container.mouseenter(function () {
                        vars.navigation.stop(true, true).fadeIn(settings.controlFadeTime);
                    }).mouseleave(function () {
                        vars.navigation.delay(200).fadeOut(settings.controlFadeTime);
                    });
                }

                //add captions
                if (settings.showCaptions !== "never") {
                    vars.container.addClass(vars.prefix + "show-captions");
                    vars.items.each(function () {
                        var $item = $(this);
                        if ($item.children("." + vars.prefix + "caption").length === 0) {
                            if ($item.children("img").length > 0) {
                                var title = $.trim($item.children("img:first").attr("title"));
                                if(typeof(title) !== "undefined" || "" === title){
                                    $item.append("<div class=\"" + vars.prefix + "caption\">" + title + "</div>");
                                    $item.children("." + vars.prefix + "caption:empty").remove();
                                }
                            }
                        }
                    });

                    if (settings.showCaptions === "hover") {
                        $("." + vars.prefix + "caption").hide();
                        vars.container.mouseenter(function () {
                            vars.active.find("." + vars.prefix + "caption").stop(true, true).fadeTo(settings.captionFadeTime, settings.captionsOpacity);
                        }).mouseleave(function () {
                            vars.active.find("." + vars.prefix + "caption").delay(200).fadeOut(settings.captionFadeTime);
                        });
                    } else if (settings.showCaptions === "always") {
                        $("." + vars.prefix + "caption").fadeTo(0, settings.captionsOpacity);
                    }
                }
                //remove titles
                vars.items.each(function () {
                    $(this).children("img").removeAttr("title");
                });


                //start autoplay if set
                if (settings.autoPlay) {
                    vars.intervalAutoPlay = setInterval(function () {
                        next($slider, settings);
                    }, settings.showTime);
                } else {
                    vars.intervalAutoPlay = false;
                }
                //if pause on hover
                if (settings.pauseOnHover) {
                    vars.container.addClass(vars.prefix + "pause-on-hover");
                    //play/pause function cannot be used for they trigger the isPlaying variable
                    $slider.mouseenter(function () {
                        if (vars.isPlaying) {
                            clearInterval(vars.intervalAutoPlay);
                            if (settings.controlsPlayPause) {
                                vars.buttons.play.text(settings.playText).removeClass(vars.prefix + "pause").addClass(vars.prefix + "play");
                            }
                        }
                    }).mouseleave(function () {
                        if (vars.isPlaying) {
                            vars.intervalAutoPlay = setInterval(function () {
                                next($slider, settings);
                            }, settings.showTime);

                            if (settings.controlsPlayPause) {
                                vars.buttons.play.text(settings.pauseText).removeClass(vars.prefix + "play").addClass(vars.prefix + "pause");
                            }
                        }
                    });
                }

                //catch keyup event and trigger functions if the right key is pressed
                if (settings.controlsKeyboard) {
                    vars.container.addClass(vars.prefix + "controls-keyboard");
                    $(document).keyup(function (e) {
                        switch (e.keyCode) {
                        case 37:
                            pause();
                            prev($slider, settings);
                            break;
                        case 39:
                            pause();
                            next($slider, settings);
                            break;
                        case 80:
                            //self-explaining
                            if (vars.isPlaying === false) {
                                play();
                            } else {
                                pause();
                            }
                            break;
                        }
                    });
                }

                //catch mousewheel event and trigger prev or next
                if (settings.controlsMousewheel) {
                    vars.container.addClass(vars.prefix + "controls-mousewheel");
                    if (!$.isFunction($.fn.mousewheel)) {
                        alert("$.fn.mousewheel is not a function. Please check that you have the mousewheel-plugin installed properly.");
                    } else {
                        $slider.mousewheel(function (e, delta) {
                            e.preventDefault();
                            if(vars.container.hasClass("inProgress")){
                                return false;
                            }
                            var dir = delta > 0 ? "up" : "down";
                            if (dir === "up") {
                                pause();
                                prev($slider, settings);
                            } else {
                                pause();
                                next($slider, settings);
                            }
                        });
                    }
                }

                vars.active = $slider.find("." + vars.prefix + "active");
                vars.active.css({
                    zIndex: 1,
                    opacity: 1
                });

                //check if slider is non-cycled
                if(!settings.cycled) {
                    vars.items.each(function() {
                        var $item = $(this);
                        if($item.is(":first-child")) {
                            $item.addClass(vars.prefix + "firstItem");
                        }
                        if($item.is(":last-child")) {
                            $item.addClass(vars.prefix + "lastItem");
                        }
                    });

                    if(vars.active.is(":first-child") && settings.controlsPrevNext){
                        vars.buttons.prev.addClass("disabled");
                    }
                    if(vars.active.is(":last-child")){
                        if(settings.controlsPrevNext){
                            vars.buttons.next.addClass("disabled");
                            pause();
                        }
                        if(settings.autoPlay){
                            vars.buttons.play.addClass("disabled");
                        }
                    }
                }

                if(typeof(preparations[settings.effect]) === "undefined"){
                    console.log("Effect for " + settings.effect + " not found.");
                }else{
                    preparations[settings.effect]($slider, settings, vars);
                }

                //return the init-data to the slide for further use
                $slider.data("slider:vars", vars);

                settings.callBackInit();
            },

            //check if item element is first-child
            isFirst = function($item) {
                return $item.is(":first-child");
            },

            //check if item element is last-child
            isLast = function($item) {
                return $item.is(":last-child");
            },

            //pause the autoplay and change the bg-image of the button to "play"
            pause = function () {
                var vars = $slider.data("slider:vars");
                clearInterval(vars.intervalAutoPlay);
                vars.isPlaying = false;
                if (settings.controlsPlayPause) {
                    vars.buttons.play.text(settings.playText).removeClass(vars.prefix + "pause").addClass(vars.prefix + "play");
                }

                settings.callBackPause();
            },

            //start/resume the autoplay and change the bg-image of the button to "pause"
            play = function () {
                var vars = $slider.data("slider:vars");
                vars.intervalAutoPlay = setInterval(function () {
                    next($slider, settings);
                }, settings.showTime);
                vars.isPlaying = true;
                if (settings.controlsPlayPause) {
                    vars.buttons.play.text(settings.pauseText).removeClass(vars.prefix + "play").addClass(vars.prefix + "pause");
                }

                settings.callBackPlay();
            },

            prev = function ($slider, settings, $next) {
                var vars = $slider.data("slider:vars");
                if(!settings.cycled && isFirst(vars.active)){
                    return false;
                }

                settings.callBeforePrev();

                //if some effect is already running, don"t stack up another one
                if (vars.container.hasClass("inProgress")) {
                    return false;
                }
                vars.container.addClass("inProgress");

                if (!$next) {
                    if (settings.randomOrder) {
                        var nextID = getRandom(vars);
                        vars.next = vars.container.find("#" + nextID);
                    } else {
                        vars.next = vars.items.first().hasClass(vars.prefix + "active") ? vars.items.last() : vars.active.prev();
                    }
                } else {
                    vars.next = $next;
                }

                if (vars.next.hasClass(vars.prefix + "active")) {
                    return false;
                }

                //hide captions
                if (settings.showCaptions !== "never") {
                    $("." + vars.prefix + "caption").stop(true, true).fadeOut(settings.captionsFadeTime);
                }

                if (settings.showBullets !== "never" && settings.changeBullets === "before") {
                    vars.navigation.find("." + vars.prefix + "active-bullet").removeClass(vars.prefix + "active-bullet");
                    vars.navigation.find("#" + vars.next.attr("id") + "-bullet").addClass(vars.prefix + "active-bullet");
                }

                setTimeout(function() {
                    var params = [];
                    params.settings = settings;
                    params.animateActive = settings.animateActive;
                    params.direction = settings.slidePrevDirection;

                    if(type0f(effects[settings.effect]) === "undefined"){
                        console.log("Preparations for " + settings.effect + " not found.");
                    }else{
                        effects[settings.effect]($slider, params, resetElements);
                    }

                    setTimeout(function () {
                        if (settings.showBullets !== "never" && settings.changeBullets === "after") {
                            vars.navigation.find("." + vars.prefix + "active-bullet").removeClass(vars.prefix + "active-bullet");
                            vars.navigation.find("#" + vars.next.attr("id") + "-bullet").addClass(vars.prefix + "active-bullet");
                        }
                        settings.callBackPrev();
                    }, settings.effectTime);
                }, settings.captionsFadeTime);

                if (settings.showBullets !== "never" && settings.changeBullets === "after") {
                    vars.navigation.find("." + vars.prefix + "active-bullet").removeClass(vars.prefix + "active-bullet");
                    vars.navigation.find("#" + vars.next.attr("id") + "-bullet").addClass(vars.prefix + "active-bullet");
                }
            },

            next = function ($slider, settings, $next) {
                var vars = $slider.data("slider:vars");
                if(!settings.cycled && isLast(vars.active)){
                    return false;
                }

                settings.callBeforeNext();

                //if some effect is already running, don"t stack up another one
                if (vars.container.hasClass("inProgress")) {
                    return false;
                }
                vars.container.addClass("inProgress");
                //check, if the active element is the last, so we can set the first element to be the "next"-element
                if (!$next) {
                    if (settings.randomOrder) {
                        var nextID = getRandom(vars);
                        vars.next = vars.container.find("#" + nextID);
                    } else {
                        vars.next = vars.items.last().hasClass(vars.prefix + "active") ? vars.items.first() : vars.active.next();
                    }
                } else {
                    vars.next = $next;
                }

                if (vars.next.hasClass(vars.prefix + "active")) {
                    return false;
                }

                //hide captions
                if (settings.showCaptions !== "never") {
                    $("." + vars.prefix + "caption").stop(true, true).fadeOut(settings.captionsFadeTime);
                }

                if (settings.showBullets !== "never" && settings.changeBullets === "before") {
                    vars.navigation.find("." + vars.prefix + "active-bullet").removeClass(vars.prefix + "active-bullet");
                    vars.navigation.find("#" + vars.next.attr("id") + "-bullet").addClass(vars.prefix + "active-bullet");
                }

                setTimeout(function() {
                    var params = [];
                    params.settings = settings;
                    params.animateActive = settings.animateActive;
                    params.direction = settings.slideNextDirection;

                    //run effect
                    if(typeof(effects[settings.effect]) === "undefined"){
                        console.log("Preparations for " + settings.effect + " not found.");
                    }else{
                        effects[settings.effect]($slider, params, resetElements);
                    }

                    setTimeout(function () {
                        if (settings.showBullets !== "never" && settings.changeBullets === "after") {
                            vars.navigation.find("." + vars.prefix + "active-bullet").removeClass(vars.prefix + "active-bullet");
                            vars.navigation.find("#" + vars.next.attr("id") + "-bullet").addClass(vars.prefix + "active-bullet");
                        }
                        settings.callBackNext();
                    }, settings.effectTime);

                }, settings.captionsFadeTime);
            },

            //get random itemID
            getRandom = function (vars) {
                var curID = vars.active.attr("id");
                var itemCount = vars.items.length;
                var nextID = vars.prefix + "item" + parseInt((Math.random() * itemCount), 10);
                var nextKey = nextID.replace(vars.prefix + "item", "");
                if (vars.playedCounter >= itemCount) {
                    vars.playedCounter = 0;
                    vars.playedArray = [];
                }
                if (curID === nextID || vars.playedArray[nextKey] === true) {
                    return getRandom(vars);
                } else {
                    vars.playedArray[nextKey] = true;
                    vars.playedCounter++;
                    return nextID;
                }
            },

            //function to reset elements and style after an effect
            resetElements = function ($slider, settings) {
                var vars = $slider.data("slider:vars");
                //set the active-element on the same z-index as the rest and reset css
                vars.next
                    //add the active-class
                    .addClass(vars.prefix + "active")
                    //and put  it above the others
                    .css({
                        zIndex: 1,
                        top: 0,
                        left: 0,
                        width: "100%",
                        height: "100%",
                        margin: 0,
                        opacity: 1
                    });
                vars.active
                    .css({
                        zIndex: 0,
                        top: 0,
                        left: 0,
                        margin: 0,
                        opacity: 0
                    })
                    //and remove its active class
                    .removeClass(vars.prefix + "active");

                settings.additionalResets();

                //check if cycled is false and start or end is reached
                if(!settings.cycled) {
                    if(settings.controlsPrevNext){
                        if(isFirst(vars.next)) {
                            vars.buttons.prev.addClass("disabled");
                        } else {
                            vars.buttons.prev.removeClass("disabled");
                        }
                        if(isLast(vars.next)) {
                            vars.buttons.next.addClass("disabled");
                            pause();
                        } else {
                            vars.buttons.next.removeClass("disabled");
                        }
                    }
                    if(settings.controlsPlayPause){
                        if(isLast(vars.next)) {
                            vars.buttons.play.addClass("disabled");
                            pause();
                        } else {
                            vars.buttons.play.removeClass("disabled");
                        }
                    }
                }

                if (settings.showBullets !== "never") {

                    vars.navigation.find("." + vars.prefix + "active-bullet").removeClass(vars.prefix + "active-bullet");
                    vars.navigation.find("#" + vars.next.attr("id") + "-bullet").addClass(vars.prefix + "active-bullet");
                }

                //make the "next"-element the new active-element
                vars.active = vars.next;

                //show captions
                if (settings.showCaptions !== "never") {
                    vars.active.find("." + vars.prefix + "caption").stop(true, true).fadeTo(settings.captionsFadeTime, settings.captionsOpacity);
                }

                vars.container.removeClass("inProgress");
            };

        this.pause = function () {pause();};
        this.play = function () {play();};
        this.prev = function ($next) {prev($slider, settings, $next);};
        this.next = function ($next) {next($slider, settings, $next);};
        this.uninit = function () {
            pause();
            vars.container.before($(element).data("slider:original"));
            $slider.data("slider:vars", null);
            vars.container.remove();
            $(element).data("rhinoslider", null);
        };

        init($slider, settings, vars);
    };

    $.fn.rhinoslider = function (opts) {
        return this.each(function () {
            var element = $(this);
            if (element.data("rhinoslider")) {
                return element.data("rhinoslider");
            }

            element.data("slider:original", element.clone());
            var rhinoslider = new rhinoSlider(this, opts);
            element.data("rhinoslider", rhinoslider);
        });
    };

    $.fn.rhinoslider.defaults = {
        //which effect to blend content
        effect: "slide",
        //easing for animations of the slides
        easing: "swing",
        //linear or shuffled order for items
        randomOrder: false,
        //enable/disable mousewheel navigation
        controlsMousewheel: true,
        //enable/disable keyboard navigation
        controlsKeyboard: true,
        //show/hide prev/next-controls
        controlsPrevNext: true,
        //show/hide play/pause-controls
        controlsPlayPause: true,
        //pause on mouse-over
        pauseOnHover: true,
        //if the active content should be animated too - depending on effect slide
        animateActive: true,
        //start slideshow automatically on init
        autoPlay: false,
        //begin from start if end has reached
        cycled: true,
        //time, the content is visible before next content will be blend in - depends on autoPlay
        showTime: 3000,
        //time, the effect will last
        effectTime: 1000,
        //duration for fading controls
        controlFadeTime: 650,
        //duration for fading captions
        captionsFadeTime: 250,
        //opacity for captions
        captionsOpacity: 0.7,
        //delay for parts in "chewyBars" effect
        partDelay: 100,
        //width, the animation for moving the content needs, can be comma-seperated string (x,y) or int if both are the same
        shiftValue: "150",
        //amount of parts per line for shuffle effect
        parts: "5,3",
        //show image-title: hover, always, never
        showCaptions: "never",
        //show navigation: hover, always, never
        showBullets: "hover",
        //change bullets before or after the animation
        changeBullets: "after",
        //show controls: hover, always, never
        showControls: "hover",
        //the direction, the prev-button triggers - depending on effect slide
        slidePrevDirection: "toLeft",
        //the direction, the next-button triggers - depending on effect slide
        slideNextDirection: "toRight",
        //text for the prev-button
        prevText: "prev",
        //text for the next-button
        nextText: "next",
        //text for the play-button
        playText: "play",
        //text for the pause-button
        pauseText: "pause",
        //style which will be transfered to the containerelement
        styles: "position,top,right,bottom,left,margin-top,margin-right,margin-bottom,margin-left,width,height",
        //callbacks
        //the function, which is started bofore anything is done by this script
        callBeforeInit() {
            return false;
        },
        //the function, which is started when the slider is ready (only once)
        callBackInit() {
            return false;
        },
        //the function, which is started before the blending-effect
        callBeforeNext() {
            return false;
        },
        //the function, which is started before the blending-effect
        callBeforePrev() {
            return false;
        },
        //the function, which is started after the blending-effect
        callBackNext() {
            return false;
        },
        //the function, which is started after the blending-effect
        callBackPrev() {
            return false;
        },
        //the function, which is started if the autoplay intervall starts
        callBackPlay() {
            return false;
        },
        //the function, which is started if the autoplay intervall ends
        callBackPause() {
            return false;
        },
        //the function, which is started within resetElements
        additionalResets() {
            return false;
        }
    };

    $.fn.rhinoslider.effects = {
        //options: direction, animateActive, easing
        slide($slider, params, callback) {
            var vars = $slider.data("slider:vars");
            var settings = params.settings;
            var direction = params.direction;
            var values = [];
            values.width = vars.container.width();
            values.height = vars.container.height();
            //if showtime is 0, content is sliding permanently so linear is the way to go
            values.easing = settings.showTime === 0 ? "linear" : settings.easing;
            values.nextEasing = settings.showTime === 0 ? "linear" : settings.easing;
            $slider.css("overflow", "hidden");

            //check, in which direction the content will be moved
            switch (direction) {
                case "toTop":
                    values.top = -values.height;
                    values.left = 0;
                    values.nextTop = -values.top;
                    values.nextLeft = 0;
                    break;
                case "toBottom":
                    values.top = values.height;
                    values.left = 0;
                    values.nextTop = -values.top;
                    values.nextLeft = 0;
                    break;
                case "toRight":
                    values.top = 0;
                    values.left = values.width;
                    values.nextTop = 0;
                    values.nextLeft = -values.left;
                    break;
                case "toLeft":
                    values.top = 0;
                    values.left = -values.width;
                    values.nextTop = 0;
                    values.nextLeft = -values.left;
                    break;
            }

            //put the "next"-element on top of the others and show/hide it, depending on the effect
            vars.next.css({
                zIndex: 2,
                opacity: 1
            });

            //if animateActive is false, the active-element will not move
            if (settings.animateActive) {
                vars.active.css({
                    top: 0,
                    left: 0
                }).animate({
                    top: values.top,
                    left: values.left,
                    opacity: 1
                }, settings.effectTime, values.easing);
            }
            vars.next
            //position "next"-element depending on the direction
            .css({
                top: values.nextTop,
                left: values.nextLeft
            }).animate({
                top: 0,
                left: 0,
                opacity: 1
            }, settings.effectTime, values.nextEasing, function () {
                //reset element-positions
                callback($slider, settings);
            });
        }
    };

    $.fn.rhinoslider.preparations = {
        slide($slider, settings, vars) {
            vars.items.css("overflow", "hidden");
            $slider.css("overflow", "hidden");
        }
    };

})(jQuery, window);