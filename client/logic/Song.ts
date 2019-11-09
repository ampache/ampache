import axios from "axios";

type Song = {
    id: number,
    title: string,
    artist: {
        id: number,
        name: string
    },
    album: {
        id: number,
        name: string
    },
    albumartist: {
        id: number,
        name: string
    }
    filename: string,
    track: number,
    playlisttrack: number,
    time: number,
    year: number,
    bitrate: number,
    rate: number,
    mode: string,
    mime: string,
    url: string,
    size: number,
    mbid: string,
    album_mbid: string,
    artist_mbid: string,
    albumartist_mbid: string,
    art: string,
    preciserating: number,
    rating: number,
    averagerating: number,
    composer: string,
    channels: number,
    comment: string,
    publiser: string,
    language: string,
    replaygain_album_gain: number,
    replaygain_album_peak: number,
    replaygain_track_gain: number,
    replaygain_track_peak: number,
    tags: Array<String>
}

export {Song};

