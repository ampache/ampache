import axios from 'axios';
import { AuthKey } from './Auth';
import AmpacheError from './AmpacheError';

//The API returns different information depending on if you are requesting yourself, or are an admin. Look at UserMethod.php:78
type User = {
    authKey: AuthKey;
    id: string;
    username: string;
    auth?: AuthKey;
    email?: string;
    access?: string;
    fullname_public?: boolean;
    validation?: string;
    disabled?: boolean;
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
            return JSONData as User;
        });
};
export { getUser, User };
