import axios from 'axios';
import { Song } from './Song';
import { AuthKey } from './Auth';

const searchSongs = async (
    searchQuery: string,
    authKey: AuthKey,
    server: string
) => {
    return axios
        .get(
            `${server}/server/json.server.php?action=search_songs&filter=${searchQuery}&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new Error(JSONData.error);
            }
            return JSONData as Song[];
        });
};

export { searchSongs };
