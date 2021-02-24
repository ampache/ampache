import { AuthKey } from '~logic/Auth';
import axios from '~node_modules/axios';
import AmpacheError from '~logic/AmpacheError';

const flagItem = (
    type:
        | 'song'
        | 'album'
        | 'artist'
        | 'playlist'
        | 'podcast'
        | 'podcast_episode'
        | 'video'
        | 'tvshow'
        | 'tvshow_season',
    objectID: string,
    favorite: boolean,
    authKey: AuthKey
) => {
    return axios
        .get(
            `${
                process.env.ServerURL
            }/server/json.server.php?action=flag&type=${type}&id=${objectID}&flag=${Number(
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

export default flagItem;
