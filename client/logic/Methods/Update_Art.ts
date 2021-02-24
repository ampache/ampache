import { AuthKey } from '~logic/Auth';
import axios from '~node_modules/axios';
import AmpacheError from '~logic/AmpacheError';

const updateArt = (
    type: 'song' | 'artist' | 'album' | 'playlist' | 'search' | 'podcast',
    ID: string,
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

export default updateArt;
