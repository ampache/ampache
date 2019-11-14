import axios from 'axios';
import { AuthKey } from './Auth';
import AmpacheError from './AmpacheError';

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
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error && !JSONData.error.contains('Request Aborted')) {
                throw new AmpacheError(JSONData.error);
            }
            return JSONData.user as User;
        });
};
export { getUser, User };
