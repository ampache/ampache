import axios from 'axios';
import { AuthKey } from './Auth';
import AmpacheError from './AmpacheError';

type User = {
    authKey: AuthKey;
    id: number;
    username: string;
    email: string;
    access: number;
    fullname_public: boolean;
    disabled: boolean;
    create_date: number;
    last_seen: number;
    website: string;
    state: string;
    city: string;
};

const getUser = (username: string, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=user&username=${username}&auth=${authKey}&version=350001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                if (JSONData.error.errorMessage.includes('Request Aborted'))
                    //TODO
                    return;
                throw new AmpacheError(JSONData.error);
            }
            return JSONData.user as User;
        });
};
export { getUser, User };
