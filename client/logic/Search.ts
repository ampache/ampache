import axios from 'axios';
import { Song } from './Song';
import { AuthKey } from './Auth';
import { Album } from './Album';

const searchSongs = (searchQuery: string, authKey: AuthKey, limit = 100) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=search_songs&filter=${searchQuery}&limit=${limit}&auth=${authKey}&version=400001`
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

const searchAlbums = (searchQuery: string, authKey: AuthKey, limit = 100) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=advanced_search&rule_1=title&rule_1_operator=0&rule_1_input=${searchQuery}&rule_2=artist&rule_2_operator=0&rule_2_input=${searchQuery}&type=album&operator=or&limit=${limit}&auth=${authKey}&version=400001
            `
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new Error(JSONData.error);
            }
            return JSONData.album as Album[];
        });
};

export { searchSongs, searchAlbums };
