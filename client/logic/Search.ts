import axios from 'axios';
import { Song } from './Song';
import { AuthKey } from './Auth';

const searchSongs = (searchQuery: string, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=search_songs&filter=${searchQuery}&auth=${authKey}&version=400001`
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
