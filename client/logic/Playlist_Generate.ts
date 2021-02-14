import axios from 'axios';
import { Song } from './Song';
import { AuthKey } from './Auth';
import AmpacheError from '~logic/AmpacheError';

//Broken
export const generateSongsFromArtist = (artistID: number, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=playlist_generate&artist=${artistID}&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new Error(JSONData.error);
            }
            return JSONData.song as Song[];
        });
};

export const generateSongsFromAlbum = (albumID: number, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=playlist_generate&album=${albumID}&auth=${authKey}&version=400001`
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
