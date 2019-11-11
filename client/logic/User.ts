import axios from 'axios';
import { AuthKey } from './Auth';

type User = {
    authKey: AuthKey;
    city: string;
    create_date: number;
    id: number;
    last_seen: number;
    state: string;
    username: string;
    website: string;
};

const getUser = async (username: string, authKey: AuthKey, server: string) => {
    return axios
        .get(
            `${server}/server/json.server.php?action=user&username=${username}&auth=${authKey}&version=350001`
        )
        .then((response): User | Error => {
            let JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new Error(JSONData.error);
            }
            return JSONData;
        });
};
export { getUser, User };
