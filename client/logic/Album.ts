import axios from 'axios';
import { Song } from './Song';
import { AuthKey } from './Auth';

type Album = {
    id: number;
    name: string;
    artist: {
        id: number;
        name: string;
    };
    year: number;
    tracks: number;
    disk: number;
    tags: {
        id: number;
        count: number;
        name: string;
    };
    art: string;
    preciserating: number;
    rating: number;
    averagerating: number;
    mbid: string;
};

const getRandomAlbums = async (
    username: string,
    count: number,
    authKey: AuthKey,
    server: string
) => {
    return axios
        .get(
            `${server}/server/json.server.php?action=stats&username=${username}&limit=${count}&auth=${authKey}&version=400001`
        )
        .then((response): Album[] | Error => {
            let JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new Error(JSONData.error);
            }
            return JSONData;
        });
};

const getAlbumSongs = async (
    albumID: number,
    authKey: AuthKey,
    server: string
) => {
    return axios
        .get(
            `${server}/server/json.server.php?action=album_songs&filter=${albumID}&auth=${authKey}&version=400001`
        )
        .then((response): Song[] | Error => {
            let JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new Error(JSONData.error);
            }
            return JSONData;
        });
};

const getAlbum = async (albumID: number, authKey: AuthKey, server: string) => {
    return axios
        .get(
            `${server}/server/json.server.php?action=album&filter=${albumID}&auth=${authKey}&version=400001`
        )
        .then((response): Album | Error => {
            let JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new Error(JSONData.error);
            }
            return JSONData[0];
        });
};

export { getRandomAlbums, Album, getAlbum, getAlbumSongs };
