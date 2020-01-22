import axios from 'axios';
import { AuthKey } from './Auth';
import AmpacheError from './AmpacheError';
import { Album } from './Album';

export type Artist = {
    id: number;
    name: string;
    tags: [];
    albums: number;
    songs: number;
    art: string;
    preciserating: number;
    rating: number;
    averagerating: number;
    mbid: string;
    summary: string;
    yearformed: number;
    placeformed: string;
};

export const getAlbumsFromArtist = (albumID: number, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=artist_albums&filter=${albumID}&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return JSONData as Album[];
        });
};

export const getArtists = (authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=artists&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            return JSONData as Artist[];
        });
};
