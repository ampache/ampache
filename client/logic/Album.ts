import axios from 'axios';
import { Song } from './Song';
import { AuthKey } from './Auth';
import AmpacheError from './AmpacheError';

type Album = {
    id: string;
    name: string;
    artist: {
        id: string;
        name: string;
    };
    time: number;
    year: number;
    tracks: Song[];
    songcount: number;
    disk: number;
    genre: [
        {
            id: string;
            name: string;
        }
    ];
    art: string;
    flag: boolean;
    preciserating: number;
    rating: number;
    averagerating: number;
    mbid: string;
};

const getRandomAlbums = (username: string, count: number, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=stats&username=${username}&type=album&filter=random&limit=${count}&auth=${authKey}&version=500001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return JSONData.album as Album[];
        });
};

const getAlbumSongs = (albumID: string, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=album_songs&filter=${albumID}&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return JSONData.song as Song[];
        });
};

const getAlbums = (authKey: AuthKey, includeSongs = false) => {
    let includeString = '';
    if (includeSongs) {
        includeString += '&include[]=songs';
    }
    const getUrl = `${process.env.ServerURL}/server/json.server.php?action=albums&auth=${authKey}${includeString}&version=400001`;
    return axios.get(getUrl).then((response) => {
        const JSONData = response.data;
        if (!JSONData) {
            throw new Error('Server Error');
        }
        if (JSONData.error) {
            throw new AmpacheError(JSONData.error);
        }
        return JSONData.album as Album[];
    });
};

const getAlbum = (albumID: string, authKey: AuthKey, includeSongs = false) => {
    let includeString = '';
    if (includeSongs) {
        includeString += '&include[]=songs';
    }
    const getURL = `${process.env.ServerURL}/server/json.server.php?action=album&filter=${albumID}${includeString}&auth=${authKey}&version=400001`;
    return axios.get(getURL).then((response) => {
        const JSONData = response.data;
        if (!JSONData) {
            throw new Error('Server Error');
        }
        if (JSONData.error) {
            throw new AmpacheError(JSONData.error);
        }
        return JSONData.album[0] as Album;
    });
};

export { getRandomAlbums, Album, getAlbums, getAlbum, getAlbumSongs };
