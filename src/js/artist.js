export function topTracksIndexes() {
    var index = 1;
    var indexes = $("#top_tracks .cel_play_content").each(function() {
        $(this).html(index++);
    });
}