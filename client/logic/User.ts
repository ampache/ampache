import { AuthKey } from './Auth';
import AmpacheError from './AmpacheError';
import { useQuery } from '@tanstack/react-query';
import { OptionType } from '~types';
import Cookies from 'js-cookie';
import axios from 'axios';

//The API returns different information depending on if you are requesting yourself, or are an admin. Look at UserMethod.php:78
export type User = {
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

export const getUser = (specifiedUsername?: string) => {
    const authKey = Cookies.get('authKey');
    const username = specifiedUsername || Cookies.get('username');
    if (!username || !authKey) return null;
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

export const useGetUser = (options?: OptionType<User>) => {
    return useQuery<User, Error | AmpacheError>(['user'], () => getUser(), {
        retry: false,
        ...options
    });
};
