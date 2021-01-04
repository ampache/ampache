import { AuthKey } from './Auth';
import axios from 'axios';
import AmpacheError from './AmpacheError';

type Song = {
    id: number;
    title: string;
    name: string;
    artist: {
        id: number;
        name: string;
    };
    album: {
        id: number;
        name: string;
    };
    genre: { id: number; name: string };
    albumartist: {
        id: number;
        name: string;
    };
    filename: string;
    track: number;
    playlisttrack: number;
    time: number;
    year: number;
    bitrate: number;
    rate: number;
    mode: string;
    mime: string;
    url: string;
    size: number;
    mbid: string;
    album_mbid: string;
    artist_mbid: string;
    albumartist_mbid: string;
    art: string;
    flag: boolean;
    preciserating: number;
    rating: number;
    averagerating: number;
    playcount: number;
    catalog: number;
    composer: string;
    channels: number;
    comment: string;
    license?: string;
    publiser: string;
    language: string;
    replaygain_album_gain: number;
    replaygain_album_peak: number;
    replaygain_track_gain: number;
    replaygain_track_peak: number;
};

export const flagSong = (
    songID: number,
    favorite: boolean,
    authKey: AuthKey
) => {
    return axios
        .get(
            `${
                process.env.ServerURL
            }/server/json.server.php?action=flag&type=song&id=${songID}&flag=${Number(
                favorite
            )}&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return true;
        });
};

export { Song };
