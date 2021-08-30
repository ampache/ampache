/* global jPlayerPlaylist, jplaylist */

"use strict";

jPlayerPlaylist.prototype._createListItem = function(media) {
        var self = this;

        // Wrap the <li> contents in a <div>
        var attrClass = " class=\"playlist-row ";
        if ("string" === typeof media.attrClass) {
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
};

// TODO: take away the playlistName
jPlayerPlaylist.prototype.rmTrack = function(trackId, playlistName) {
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
};


jPlayerPlaylist.prototype.remove = function(index) {
    var self = this;

    // console.log("remove track " + index);

    if (typeof index === "undefined") {
        this._initPlaylist([]);
        this._refresh(function() {
            $(self.cssSelector.jPlayer).jPlayer("clearMedia");
            self.scan();
        });
        return true;
    } else {

        if (this.removing) {
            return false;
        } else {
            index = (index < 0) ? self.original.length + index : index; // Negative index relates to end of array.
            if (0 <= index && index < this.playlist.length) {
                this.removing = true;

                if ("playlist" === jplaylist.type) {
                    console.log("delete track index " + index);
                    var trackId = $($(".jp-playlist-item-remove")[index]).parent().parent().attr("track_id");
                    jplaylist.rmTrack(trackId, jplaylist.name);
                }

                $(this.cssSelector.playlist + " li:nth-child(" + (index + 1) + ")").slideUp(this.options.playlistOptions.removeTime, function() {
                    $(this).remove();

                    if (self.shuffled) {
                        var item = self.playlist[index];
                        $.each(self.original, function(i,v) {
                            if (self.original[i] === item) {
                                self.original.splice(i, 1);
                                return false; // Exit $.each
                            }
                        });
                        self.playlist.splice(index, 1);
                    } else {
                        self.original.splice(index, 1);
                        self.playlist.splice(index, 1);
                    }

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
                    self.scan();
                });
            }
            return true;
        }
    }
};

jPlayerPlaylist.prototype.removeAll = function() {
    this.original = [];
    this._originalPlaylist();
    $(this.cssSelector.playlist + " ul").html(" ");
};

jPlayerPlaylist.prototype.scan = function() {
    var self = this;
    var isAdjusted = false;

    var replace = [];
    var maxName = 0; // maximum value that name attribute assumes.
    $.each($(this.cssSelector.playlist + " ul li"), function(index, value) {
        if ($(value).attr("name") > maxName) {
            maxName = parseInt($(value).attr("name"), 10);
        }
    });

    var diffCount = maxName + 1 !== $(this.cssSelector.playlist + " ul li").length; // Flag that marks if the number of "ul li" elements doesn't match the name attribute counting.

    $.each($(this.cssSelector.playlist + " ul li"), function(index, value) {
        if (!diffCount) {
            replace[index] = self.original[$(value).attr("name")];
            if (!isAdjusted && self.current === parseInt($(value).attr("name"), 10)) {
                self.current = index;
                isAdjusted = true;
            }
        }
        $(value).attr("name", index);
    });

    if (!diffCount) {
        this.original = replace;
        this._originalPlaylist();
    }
};


jPlayerPlaylist.prototype.setCurrent = function(current) {
    this.current = current;
    this.select(this.current);
};

jPlayerPlaylist.prototype.play = function(index) {
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
};

jPlayerPlaylist.prototype._highlight = function(index) {
    $(this.cssSelector.title + " li:first").html("Playlist: " + this.name);
    $(this.cssSelector.title + " li:last").html(" ");
    if (this.playlist.length && typeof index !== "undefined") {
        $(this.cssSelector.playlist + " .jp-playlist-current").removeClass("jp-playlist-current");
        $(this.cssSelector.playlist + " li:nth-child(" + (index + 1) + ")").addClass("jp-playlist-current").find(".jp-playlist-item").addClass("jp-playlist-current");
        $(this.cssSelector.title + " li:last").html(this.playlist[index].title + (this.playlist[index].artist ? " <span class='jp-artist'>by " + this.playlist[index].artist + "</span>" : ""));
    }
};

jPlayerPlaylist.prototype.addAfter = function(media, idx) {
    if (idx >= this.original.length || idx < 0) {
        console.log("jPlayerPlaylist.addAfter: ERROR, Index out of bounds");
        return;
    }

    $(this.cssSelector.playlist + " ul")
        .find("li[name=" + idx + "]").after(this._createListItem(media)).end()
        .find("li:last-child").hide().slideDown(this.options.playlistOptions.addTime);

    this._updateControls();
    this.original.splice(idx + 1, 0, media);
    this.playlist.splice(idx + 1, 0, media);

    if(this.original.length === 1) {
        this.select(0);
    }
};

$(document).ready(function() {
});
