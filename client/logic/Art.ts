import { AuthKey } from './Auth';
import axios from 'axios';
import AmpacheError from './AmpacheError';

enum artType {
    'song' = 'song',
    'artist' = 'artist',
    'album' = 'album',
    'playlist' = 'playlist',
    'search' = 'search',
    'podcast' = 'podcast'
}

const updateArt = (
    ID: string,
    type: artType,
    overwrite: boolean,
    authKey: AuthKey
) => {
    return axios
        .get(
            `${
                process.env.ServerURL
            }/server/json.server.php?action=update_art&type=${type}&id=${ID}&overwrite=${
                overwrite ? 1 : 0
            }&auth=${authKey}&version=400001`
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
    ID: string,
    overwrite: boolean,
    authKey: AuthKey
) => {
    return updateArt(ID, artType.artist, overwrite, authKey);
};
export const updateAlbumArt = (
    ID: string,
    overwrite: boolean,
    authKey: AuthKey
) => {
    return updateArt(ID, artType.album, overwrite, authKey);
};
