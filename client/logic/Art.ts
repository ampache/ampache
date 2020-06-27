import { AuthKey } from './Auth';
import axios from 'axios';
import AmpacheError from './AmpacheError';

enum artType {
    'artist' = 'artist',
    'album' = 'album'
}

const updateArt = (
    ID: number,
    type: artType,
    overwrite: boolean,
    authKey: AuthKey
) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=update_art&type=${type}&id=${ID}&overwrite=${overwrite}&auth=${authKey}&version=400001`
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

export const updateArtistArt = (
    ID: number,
    overwrite: boolean,
    authKey: AuthKey
) => {
    return updateArt(ID, artType.artist, overwrite, authKey);
};
export const updateAlbumArt = (
    ID: number,
    overwrite: boolean,
    authKey: AuthKey
) => {
    return updateArt(ID, artType.album, overwrite, authKey);
};
