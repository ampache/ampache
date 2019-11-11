import axios from 'axios';

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
 * @param server
 * @return {Promise<AuthKey>}
 */
const handshake = async (
    username: string,
    password: string,
    server: string
) => {
    let time = Math.round(new Date().getTime() / 1000);
    const key = await digestMessage(password);
    const passphrase = await digestMessage(time + key);
    return axios
        .get(
            `${server}/server/json.server.php?action=handshake&user=${username}&timestamp=${time}&auth=${passphrase}&version=350001`
        )
        .then((response): AuthKey | Error => {
            let JSONData = response.data;

            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new Error(JSONData.error);
            }
            if (JSONData.auth) {
                console.log(JSONData.auth);
                return JSONData.auth as AuthKey;
            }
            throw new Error('Missing Auth Key'); //TOOD: Is this needed?
        });
};
export default handshake;
