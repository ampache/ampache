import axios from 'axios';

type User = {
    authCode: string;
    city: string;
    create_date: number;
    id: number;
    last_seen: number;
    state: string;
    username: string;
    website: string;
};

const getUser = async (username: string, authKey: string, server: string) => {
    return axios
        .get(
            `${server}/server/json.server.php?action=user&username=${username}&auth=${authKey}&version=350001`
        )
        .then((response) => {
            let JSONData = response.data;
            if (JSONData.error) {
                throw JSONData.error;
            }
            if (JSONData.user) {
                const user: User = JSONData.user;
                return user;
            }
            throw 'Something wrong with JSON';
        })
        .catch((error) => {
            throw error;
        });
};
export { getUser, User };
