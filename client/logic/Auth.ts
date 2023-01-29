import axios from 'axios';
import AmpacheError from './AmpacheError';

async function digestMessage(message) {
    const msgUint8 = new TextEncoder().encode(message); // encode as (utf-8) Uint8Array
    const hashBuffer = await crypto.subtle.digest('SHA-256', msgUint8); // hash the message
    const hashArray = Array.from(new Uint8Array(hashBuffer)); // convert buffer to byte array
    // convert bytes to hex string
    return hashArray.map((b) => b.toString(16).padStart(2, '0')).join('');
}

export type AuthKey = {
    string;
};

/**
 * @async
 * @param username
 * @param password
 * @return {Promise<AuthKey>}
 */
const handshake = async (username: string, password: string) => {
    const time = Math.round(new Date().getTime() / 1000);
    const key = await digestMessage(password);
    const passphrase = await digestMessage(time + key);
    return axios
        .get(
            `${process.env.ServerURL}?action=handshake&user=${username}&timestamp=${time}&auth=${passphrase}&version=350001`
        )
        .then((response) => {
            const JSONData = response.data;

            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            if (JSONData.auth) {
                return JSONData.auth as AuthKey;
            }
            throw new Error('Missing Auth Key');
        });
};

export { handshake as default };
