/**
 * FORKED BY AMPACHE 2022/02/01
 * USING LAST UPDATED GIT VERSION (https://github.com/jplayer/jPlayer)
 * https://github.com/jplayer/jPlayer/blob/master/dist/add-on/jplayer.playlist.js
 */

/**
 * Playlist Object for the jPlayer Plugin
 * http://www.jplayer.org
 *
 * Copyright (c) 2009 - 2014 Happyworm Ltd
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/MIT
 *
 * Author: Mark J Panaghiston
 * Version: 2.4.1
 * Date: 19th November 2014
 *
 * Requires:
 *  - jQuery 1.7.0+
 *  - jPlayer 2.8.2+
 */

/* global jPlayerPlaylist:true */

(function($, undefined) {

    jPlayerPlaylist = function(cssSelector, playlist, options) {
        var self = this;

        this.current = 0;
        this.loop = false; // Flag used with the jPlayer repeat event
        this.shuffled = false;
        this.removing = false; // Flag is true during remove animation, disabling the remove() method until complete.

        this.cssSelector = $.extend({}, this._cssSelector, cssSelector); // Object: Containing the css selectors for jPlayer and its cssSelectorAncestor
        this.options = $.extend(true, {
            keyBindings: {
                next: {
                    key: 221, // ]
                    fn: function() {
                        self.next();
                    }
                },
                previous: {
                    key: 219, // [
                    fn: function() {
                        self.previous();
                    }
                },
                shuffle: {
                    key: 83, // s
                    fn: function() {
                        self.shuffle();
                    }
                }
            },
            stateClass: {
                shuffled: "jp-state-shuffled"
            }
        }, this._options, options); // Object: The jPlayer constructor options for this playlist and the playlist options

        this.playlist = []; // Array of Objects: The current playlist displayed (Un-shuffled or Shuffled)
        this.original = []; // Array of Objects: The original playlist

        this._initPlaylist(playlist); // Copies playlist to this.original. Then mirrors this.original to this.playlist. Creating two arrays, where the element pointers match. (Enables pointer comparison.)

        // Setup the css selectors for the extra interface items used by the playlist.
        this.cssSelector.details = this.cssSelector.cssSelectorAncestor + " .jp-details"; // Note that jPlayer controls the text in the title element.
        this.cssSelector.playlist = this.cssSelector.cssSelectorAncestor + " .jp-playlist";
        this.cssSelector.next = this.cssSelector.cssSelectorAncestor + " .jp-next";
        this.cssSelector.previous = this.cssSelector.cssSelectorAncestor + " .jp-previous";
        this.cssSelector.shuffle = this.cssSelector.cssSelectorAncestor + " .jp-shuffle";
        this.cssSelector.shuffleOff = this.cssSelector.cssSelectorAncestor + " .jp-shuffle-off";

        // Override the cssSelectorAncestor given in options
        this.options.cssSelectorAncestor = this.cssSelector.cssSelectorAncestor;

        // Override the default repeat event handler
        this.options.repeat = function(event) {
            self.loop = event.jPlayer.options.loop;
        };

        // Create a ready event handler to initialize the playlist
        $(this.cssSelector.jPlayer).bind($.jPlayer.event.ready, function() {
            self._init();
        });

        // Create an ended event handler to move to the next item
        $(this.cssSelector.jPlayer).bind($.jPlayer.event.ended, function() {
            self.next();
        });

        // Create a play event handler to pause other instances
        $(this.cssSelector.jPlayer).bind($.jPlayer.event.play, function() {
            $(this).jPlayer("pauseOthers");
        });

        // Create a resize event handler to show the title in full screen mode.
        $(this.cssSelector.jPlayer).bind($.jPlayer.event.resize, function(event) {
            if (event.jPlayer.options.fullScreen) {
                $(self.cssSelector.details).show();
            } else {
                $(self.cssSelector.details).hide();
            }
        });

        // Create click handlers for the extra buttons that do playlist functions.
        $(this.cssSelector.previous).click(function(e) {
            e.preventDefault();
            self.previous();
            self.blur(this);
        });

        $(this.cssSelector.next).click(function(e) {
            e.preventDefault();
            self.next();
            self.blur(this);
        });

        $(this.cssSelector.shuffle).click(function(e) {
            e.preventDefault();
            if (self.shuffled && $(self.cssSelector.jPlayer).jPlayer("option", "useStateClassSkin")) {
                self.shuffle(false);
            } else {
                self.shuffle(true);
            }
            self.blur(this);
        });
        $(this.cssSelector.shuffleOff).click(function(e) {
            e.preventDefault();
            self.shuffle(false);
            self.blur(this);
        }).hide();

        // Put the title in its initial display state
        if (!this.options.fullScreen) {
            $(this.cssSelector.details).hide();
        }

        // Remove the empty <li> from the page HTML. Allows page to be valid HTML, while not interfereing with display animations
        $(this.cssSelector.playlist + " ul").empty();

        // Create .on() handlers for the playlist items along with the free media and remove controls.
        this._createItemHandlers();

        // Instance jPlayer
        $(this.cssSelector.jPlayer).jPlayer(this.options);
    };

    jPlayerPlaylist.prototype = {
        _cssSelector: { // static object, instanced in constructor
            jPlayer: "#jquery_jplayer_1",
            cssSelectorAncestor: "#jp_container_1"
        },
        _options: { // static object, instanced in constructor
            playlistOptions: {
                autoPlay: false,
                loopOnPrevious: false,
                shuffleOnLoop: true,
                enableRemoveControls: false,
                displayTime: 'slow',
                addTime: 'fast',
                removeTime: 'fast',
                shuffleTime: 'slow',
                itemClass: "jp-playlist-item",
                freeGroupClass: "jp-free-media",
                freeItemClass: "jp-playlist-item-free",
                removeItemClass: "jp-playlist-item-remove"
            }
        },
        option: function(option, value) { // For changing playlist options only
            if (typeof value === "undefined") {
                return this.options.playlistOptions[option];
            }

            this.options.playlistOptions[option] = value;

            switch(option) {
                case "enableRemoveControls":
                    this._updateControls();
                    break;
                case "itemClass":
                case "freeGroupClass":
                case "freeItemClass":
                case "removeItemClass":
                    this._refresh(true); // Instant
                    this._createItemHandlers();
                    break;
            }
            return this;
        },
        _init: function() {
            var self = this;
            this._refresh(function() {
                if (self.options.playlistOptions.autoPlay) {
                    self.play(self.current);
                } else {
                    self.select(self.current);
                }
            });
        },
        _initPlaylist: function(playlist) {
            this.current = 0;
            this.shuffled = false;
            this.removing = false;
            this.original = $.extend(true, [], playlist); // Copy the Array of Objects
            this._originalPlaylist();
        },
        _originalPlaylist: function() {
            var self = this;
            this.playlist = [];
            // Make both arrays point to the same object elements. Gives us 2 different arrays, each pointing to the same actual object. ie., Not copies of the object.
            $.each(self.original, function(i) {
                self.playlist[i] = self.original[i];
            });
        },
        _refresh: function(instant) {
            /**
             * instant: Can be undefined, true or a function.
             *    undefined -> use animation timings
             *    true -> no animation
             *    function -> use animation timings and excute function at half way point.
             */
            var self = this;

            if (instant && !$.isFunction(instant)) {
                $(this.cssSelector.playlist + " ul").empty();
                $.each(self.playlist, function(i) {
                    $(self.cssSelector.playlist + " ul").append(self._createListItem(self.playlist[i]));
                });
                this._updateControls();
            } else {
                var displayTime = $(this.cssSelector.playlist + " ul").children().length ? this.options.playlistOptions.displayTime : 0;

                $(this.cssSelector.playlist + " ul").slideUp(displayTime, function() {
                    var $this = $(this);
                    $(this).empty();

                    $.each(self.playlist, function(i) {
                        $this.append(self._createListItem(self.playlist[i]));
                    });
                    self._updateControls();
                    if ($.isFunction(instant)) {
                        instant();
                    }
                    if (self.playlist.length) {
                        $(this).slideDown(self.options.playlistOptions.displayTime);
                    } else {
                        $(this).show();
                    }
                });
            }
        },
        _refreshHtmlPlaylist: function() {
            // After addAfter() and remove() functions, new items need their webPlayer indexes reset
            // _createListItem() adds the items in the right position but uses the length of the playlist for the name property
            var self = this;
            var isAdjusted = false;
            $.each($(this.cssSelector.playlist + " ul li"), function(index, value) {
                var jindex = parseInt($(value).attr("name"),10);
                if (jindex !== index) {
                    // set the self.current index value if it's going to change.
                    if (!isAdjusted && self.current === parseInt($(value).attr("name"), 10)) {
                        self.current = index;
                        isAdjusted = true;
                    }
                    // re-index the list
                    $(value).attr("name", index);
                }
            });
            //console.log(self.playlist);
        },
        _createListItem: function(media) {
            var self = this;

            // Wrap the <li> contents in a <div>
            var attrClass = " class=\"playlist-row ";
            if (typeof media.attrClass === "string") {
                attrClass += media.attrClass;
            }
            attrClass += "\" ";

            var listItem = "<li" + attrClass + " name=\"" + $(jplaylist.cssSelector.playlist + " ul li").length + "\" " +
                "data-poster=\"" + media.poster + "\" data-media_id=\"" + media.media_id + "\" data-album_id=\"" + media.album_id + "\" data-artist_id=\"" + media.artist_id + "\" " +
                "data-replaygain_track_gain=\"" + media.replaygain_track_gain + "\" data-replaygain_track_peak=\"" + media.replaygain_track_peak + "\" data-replaygain_album_gain=\"" + media.replaygain_album_gain + "\" data-replaygain_album_peak=\"" + media.replaygain_album_peak + "\" " +
                "data-r128_track_gain=\"" + media.r128_track_gain + "\" data-r128_album_gain=\"" + media.r128_album_gain + "\"" +
                "><div>";

            // Create image
            // listItem += "<img class=\"cover\" src=\"" + media.cover + "\" alt=\"" + media.title + "\"/>";

            // Create remove control
            listItem += "<a href='javascript:;' class='" + this.options.playlistOptions.removeItemClass + "'>&times;</a>";

            // Create song links
            if (media["media_id"]) {
                listItem += "<span class=\"jp-free-media menu\"><a></a></span>";
            }

            // The title is given next in the HTML otherwise the float:right on the free media corrupts in IE6/7
            listItem += "<a href='javascript:;' class='" + this.options.playlistOptions.itemClass + "' tabindex='1'>" + media.title + (media.artist ? " <span class='jp-artist'>by " + media.artist + "</span>" : "") + "</a>";
            listItem += "</div></li>";

            return listItem;
        },
        _createItemHandlers: function() {
            var self = this;
            // Create live handlers for the playlist items
            $(this.cssSelector.playlist).off("click", "a." + this.options.playlistOptions.itemClass).on("click", "a." + this.options.playlistOptions.itemClass, function(e) {
                e.preventDefault();
                var index = $(this).parent().parent().index();
                if (self.current !== index) {
                    self.play(index);
                } else {
                    $(self.cssSelector.jPlayer).jPlayer("play");
                }
                self.blur(this);
            });

            // Create live handlers that disable free media links to force access via right click
            $(this.cssSelector.playlist).off("click", "a." + this.options.playlistOptions.freeItemClass).on("click", "a." + this.options.playlistOptions.freeItemClass, function(e) {
                e.preventDefault();
                $(this).parent().parent().find("." + self.options.playlistOptions.itemClass).click();
                self.blur(this);
            });

            // Create live handlers for the remove controls
            $(this.cssSelector.playlist).off("click", "a." + this.options.playlistOptions.removeItemClass).on("click", "a." + this.options.playlistOptions.removeItemClass, function(e) {
                e.preventDefault();
                var index = $(this).parent().parent().index();
                self.remove(index);
                self.blur(this);
            });
        },
        _updateControls: function() {
            if (this.options.playlistOptions.enableRemoveControls) {
                $(this.cssSelector.playlist + " ." + this.options.playlistOptions.removeItemClass).show();
            } else {
                $(this.cssSelector.playlist + " ." + this.options.playlistOptions.removeItemClass).hide();
            }

            if (this.shuffled) {
                $(this.cssSelector.jPlayer).jPlayer("addStateClass", "shuffled");
            } else {
                $(this.cssSelector.jPlayer).jPlayer("removeStateClass", "shuffled");
            }
            if ($(this.cssSelector.shuffle).length && $(this.cssSelector.shuffleOff).length) {
                if (this.shuffled) {
                    $(this.cssSelector.shuffleOff).show();
                    $(this.cssSelector.shuffle).hide();
                } else {
                    $(this.cssSelector.shuffleOff).hide();
                    $(this.cssSelector.shuffle).show();
                }
            }
        },
        _highlight: function(index) {
            $(this.cssSelector.title + " li:first").html("Playlist: " + this.name);
            $(this.cssSelector.title + " li:last").html(" ");
            if (this.playlist.length && typeof index !== "undefined") {
                $(this.cssSelector.playlist + " .jp-playlist-current").removeClass("jp-playlist-current");
                $(this.cssSelector.playlist + " li:nth-child(" + (index + 1) + ")").addClass("jp-playlist-current").find(".jp-playlist-item").addClass("jp-playlist-current");
                $(this.cssSelector.title + " li:last").html(this.playlist[index].title + (this.playlist[index].artist ? " <span class='jp-artist'>by " + this.playlist[index].artist + "</span>" : ""));
            }
        },
        setPlaylist: function(playlist) {
            this._initPlaylist(playlist);
            this._init();
        },
        add: function(media, playNow) {
            $(this.cssSelector.playlist + " ul").append(this._createListItem(media)).find("li:last-child").hide().slideDown(this.options.playlistOptions.addTime);
            this._updateControls();
            this.original.push(media);
            this.playlist.push(media); // Both array elements share the same object pointer. Comforms with _initPlaylist(p) system.

            if (playNow) {
                this.play(this.playlist.length - 1);
            } else {
                if (this.original.length === 1) {
                    this.select(0);
                }
            }
        },
        addAfter: function(media, index) {
            if (index >= this.original.length || index < 0) {
                console.log("jPlayerPlaylist.addAfter: ERROR, Index out of bounds");
                return;
            }
            var self = this;
            var playlist_before = [];
            var playlist_after = [];
            $.each(self.playlist, function(i) {
                playlist_before[i] = self.playlist[i];
            });

            $(this.cssSelector.playlist + " ul")
                .find("li[name=" + index + "]").after(this._createListItem(media)).end()
                .find("li:last-child").hide().slideDown(this.options.playlistOptions.addTime);

            this._updateControls();

            $.each(self.playlist, function(i) {
                playlist_after.push(playlist_before[i]);
                if (i === index) {
                    playlist_after.push(media);
                }
            });
            this.playlist = playlist_after;
            this.original = playlist_after;

            if (this.original.length === 1) {
                this.select(0);
            }
            this._refreshHtmlPlaylist();
        },
        remove: function(index) {
            //console.log("remove " + index);
            var self = this;
            var playlist_before = [];
            var playlist_after = [];
            $.each(self.playlist, function(i) {
                playlist_before[i] = self.playlist[i];
            });
            if (typeof index === "undefined") {
                this._initPlaylist([]);
                this._refresh(function() {
                    $(self.cssSelector.jPlayer).jPlayer("clearMedia");
                });
                return true;
            } else {
                if (this.removing) {
                    return false;
                } else {
                    index = (index < 0) ? this.playlist.length + index : index; // Negative index relates to end of array.
                    if (0 <= index && index < this.playlist.length) {
                        this.removing = true;

                        if ("playlist" === jplaylist.type) {
                            var trackId = $($(".jp-playlist-item-remove")[index]).parent().parent().attr("track_id");
                            jplaylist.rmTrack(trackId, jplaylist.name);
                        }

                        $(this.cssSelector.playlist + " li:nth-child(" + (index + 1) + ")").slideUp(this.options.playlistOptions.removeTime, function() {
                            $(this).remove();

                            if (self.original.length) {
                                if (index === self.current) {
                                    self.current = (index < self.original.length) ? self.current : self.original.length - 1; // To cope when last element being selected when it was removed
                                    self.select(self.current);
                                } else if (index < self.current) {
                                    self.current--;
                                }
                            } else {
                                $(self.cssSelector.jPlayer).jPlayer("clearMedia");
                                self.current = 0;
                                self.shuffled = false;
                                self._updateControls();
                            }

                            self.removing = false;
                            self._refreshHtmlPlaylist();
                        });
                        $.each(self.playlist, function(i) {
                            if (i !== index) {
                                playlist_after.push(playlist_before[i]);
                            }
                        });
                        this.playlist = playlist_after;
                        this.original = playlist_after;
                    }

                    return true;
                }
            }
        },
        removeAll: function() {
            this.original = [];
            this._originalPlaylist();
            $(this.cssSelector.playlist + " ul").html(" ");
        },
        rmTrack: function(trackId, playlistName) {
            playlistName = playlistName || "default";
            // $.bootstrapMessageLoading();
            $.post("/playlist/rmtrack", {
                playlist: playlistName,
                trackId
            }, function (data) {
                // $.bootstrapMessageAuto(data[0], data[1]);
                if ("error" === data[1]) {
                    $.loadPlaylist();
                }
            }, "json").error(function (e) {
                $.bootstrapMessageAuto("An error occurred while trying to remove the track from your playlist.", "error");
            });
        },
        scan: function() {
            // scan is used when you rearrange items in the webplayer playlist (show_html5_player.inc.php)
            var self = this;
            var isAdjusted = false;
            var playlist_before = [];
            var playlist_after = [];
            $.each(self.playlist, function(i) {
                playlist_before[i] = self.playlist[i];
            });
            //console.log(playlist_before);
            $.each($(this.cssSelector.playlist + " ul li"), function(index, value) {
                var jindex = parseInt($(value).attr("name"),10);
                if (jindex !== index) {
                    // set the self.current index value if it's going to change.
                    if (!isAdjusted && self.current === jindex) {
                        self.current = index;
                        isAdjusted = true;
                    }
                    // re-index the list
                    $(value).attr("name", index);
                }
                playlist_after.push(playlist_before[jindex]);
            });
            this.playlist = playlist_after;
            this.original = playlist_after;
            //console.log(self.playlist);
        },
        select: function(index) {
            index = (index < 0) ? this.original.length + index : index; // Negative index relates to end of array.
            if (0 <= index && index < this.playlist.length) {
                this.current = index;
                this._highlight(index);
                $(this.cssSelector.jPlayer).jPlayer("setMedia", this.playlist[this.current]);
            } else {
                this.current = 0;
            }
        },
        setCurrent: function(current) {
            this.current = current;
            this.select(this.current);
        },
        play: function(index) {
            index = (index < 0) ? this.original.length + index : index; // Negative index relates to end of array.

            if ("function" === typeof this.options.callbackPlay) {
                this.options.callbackPlay(index);
            }

            if (0 <= index && index < this.playlist.length) {
                if (this.playlist.length) {
                    this.select(index);
                    $(this.cssSelector.jPlayer).jPlayer("play");
                }
            } else if (typeof index === "undefined") {
                $(this.cssSelector.jPlayer).jPlayer("play");
            }
        },
        pause: function() {
            $(this.cssSelector.jPlayer).jPlayer("pause");
        },
        next: function() {
            var index = (this.current + 1 < this.playlist.length) ? this.current + 1 : 0;

            if (this.loop) {
                // See if we need to shuffle before looping to start, and only shuffle if more than 1 item.
                if (index === 0 && this.shuffled && this.options.playlistOptions.shuffleOnLoop && this.playlist.length > 1) {
                    this.shuffle(true, true); // playNow
                } else {
                    this.play(index);
                }
            } else {
                // The index will be zero if it just looped round
                if (index > 0) {
                    this.play(index);
                }
            }
        },
        previous: function() {
            var index = (this.current - 1 >= 0) ? this.current - 1 : this.playlist.length - 1;

            if (this.loop && this.options.playlistOptions.loopOnPrevious || index < this.playlist.length - 1) {
                this.play(index);
            }
        },
        shuffle: function(shuffled, playNow) {
            var self = this;

            if (typeof shuffled === "undefined") {
                shuffled = !this.shuffled;
            }

            if (shuffled || shuffled !== this.shuffled) {

                $(this.cssSelector.playlist + " ul").slideUp(this.options.playlistOptions.shuffleTime, function() {
                    self.shuffled = shuffled;
                    if (shuffled) {
                        var item = self.playlist[self.current];
                        self.playlist.sort(function() {
                            return 0.5 - Math.random();
                        });
                        $.each(self.playlist, function(i,v) {
                            if (self.playlist[i] === item) {
                                self.current = i;
                                return false; // Exit $.each
                            }
                        });

                        self.shuffled = false;
                        self._refresh(true);
                        self.play(self.current);
                    } else {
                        self.shuffled = false;
                        self._refresh(true); // Instant
                        self.select(self.current);
                    }
                    // we only shuffle the list and then unset shuffle immediately
                    this.original = this.playlist;

                    $(this).slideDown(self.options.playlistOptions.shuffleTime);
                });
            }
        },
        blur: function(that) {
            if ($(this.cssSelector.jPlayer).jPlayer("option", "autoBlur")) {
                $(that).blur();
            }
        }
    };
})(jQuery);
