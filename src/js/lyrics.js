// Timed lyrics: follows the web player in every timed lyrics box, and runs the player's own lyrics panel.

// Playing something new rebuilds the player and its panel, so whether the panel is open lives here, not in its markup.
var lyricsPanelOpen = false;

// The playing song as the web player resolves it, or null for anything that has no lyrics.
function playingSong() {
    var playing = (typeof currentPlaybackObject === "function") ? currentPlaybackObject() : null;

    return (playing && playing.type === "song") ? String(playing.oid) : null;
}

// A line is centred in its own box, so following the song never scrolls the page itself.
function centreCurrentLine(box) {
    var current = box.children("p.lyrics_current");
    if (current.length) {
        box.stop().animate({ scrollTop: current[0].offsetTop - (box[0].clientHeight / 2) + (current.outerHeight() / 2) }, 200);
    }
}

// Full screen or a resize changes a box's height without changing the line, so each box re-centres when it resizes.
var lyricsResize = (typeof ResizeObserver === "function")
    ? new ResizeObserver(function (entries) {
        entries.forEach(function (entry) {
            centreCurrentLine($(entry.target));
        });
    })
    : null;

function highlightLyrics(box, songId, position) {
    if (lyricsResize && !box.data("resize-watched")) {
        box.data("resize-watched", true);
        lyricsResize.observe(box[0]);
    }
    var lines = box.children("p");
    var current = $();
    if (songId !== null && songId === String(box.data("song-id"))) {
        // a positive [offset:] brings the lyrics sooner, which is the same as the song being further along
        var shifted = position + (Number(box.data("offset")) || 0);
        current = lines.filter(function () {
            return Number($(this).data("start")) <= shifted;
        }).last();
        // a line the file says has ended stays dark until the next one starts
        if (current.length && current.data("end") !== undefined && shifted > Number(current.data("end"))) {
            current = $();
        }
    }
    if (current.length && current.hasClass("lyrics_current")) {
        return;
    }
    lines.removeClass("lyrics_current");
    current.addClass("lyrics_current");
    centreCurrentLine(box);
}

function songHasNoLyrics() {
    return $("#lyricsbtn").hasClass("lyrics_none");
}

// The player says whether the playing song has stored lyrics; without any the button greys and leads to the lyrics page.
export function setLyricsAvailable(available) {
    var button = $("#lyricsbtn");
    button.toggleClass("lyrics_none", !available);
    button.children("a").attr("title", button.data(available ? "title-show" : "title-none"));
}

// Load the panel for the song now playing, once per song.
function syncLyricsPanel() {
    var panel = $("#lyrics_panel");
    var songId = playingSong();
    if (!panel.length || !lyricsPanelOpen) {
        return;
    }
    // a song without stored lyrics greys the player's button; the panel steps aside until a song that has some
    if (songHasNoLyrics()) {
        panel.addClass("display-none").attr("data-song-id", String(songId));

        return;
    }
    if (panel.hasClass("display-none")) {
        panel.removeClass("display-none").attr("data-song-id", "");
    }
    if (panel.attr("data-song-id") === String(songId)) {
        return;
    }
    panel.attr("data-song-id", String(songId));
    if (songId === null) {
        $("#lyrics_panel_body").text(panel.data("empty"));

        return;
    }
    panel.find(".lyrics_panel_page").attr("href", jsWebPath + "/song.php?action=show_lyrics&song_id=" + songId);
    ajaxPut(jsAjaxUrl + "?page=song&action=lyrics&song_id=" + songId);
}

export function toggleLyricsPanel() {
    var panel = $("#lyrics_panel");
    if (!panel.length) {
        return;
    }
    // a song without lyrics has nothing to show here, so go where an editor can find some
    if (songHasNoLyrics() && playingSong() !== null) {
        NavigateTo(jsWebPath + "/song.php?action=show_lyrics&song_id=" + playingSong());

        return;
    }
    if (lyricsPanelOpen && !panel.hasClass("display-none")) {
        closeLyricsPanel();

        return;
    }
    lyricsPanelOpen = true;
    syncLyricsPanel();
}

export function closeLyricsPanel() {
    if (document.fullscreenElement && document.fullscreenElement.id === "lyrics_panel") {
        document.exitFullscreen();
    }
    lyricsPanelOpen = false;
    $("#lyrics_panel").addClass("display-none");
}

export function toggleLyricsFullscreen() {
    var panel = document.getElementById("lyrics_panel");
    if (document.fullscreenElement) {
        document.exitFullscreen();
    } else if (panel && panel.requestFullscreen) {
        panel.requestFullscreen();
    }
}

// jPlayer events bubble to the document, so this also follows a player started after the page loaded.
$(document).on("jPlayer_timeupdate.lyrics", function (event) {
    syncLyricsPanel();
    var songId = playingSong();
    var position = (event.jPlayer) ? event.jPlayer.status.currentTime * 1000 : 0;
    $(".lyrics_synced").each(function () {
        highlightLyrics($(this), songId, position);
    });
});

// A new track changes the panel straight away, not on its first time update.
$(document).on("jPlayer_loadstart.lyrics", function () {
    setTimeout(syncLyricsPanel, 0);
});
