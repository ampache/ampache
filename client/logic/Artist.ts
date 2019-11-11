import axios from 'axios';
import { AuthKey } from './Auth';
import AmpacheError from './AmpacheError';
import { Album } from './Album';

const getAlbums = async (albumID: number, authKey: AuthKey, server: string) => {
    return axios
        .get(
            `${server}/server/json.server.php?action=artist_albums&filter=${albumID}&auth=${authKey}&version=400001`
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

export { getAlbums };
