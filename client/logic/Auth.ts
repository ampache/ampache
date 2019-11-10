import axios from "axios";


async function digestMessage(message) {
    const msgUint8 = new TextEncoder().encode(message);                           // encode as (utf-8) Uint8Array
    const hashBuffer = await crypto.subtle.digest('SHA-256', msgUint8);           // hash the message
    const hashArray = Array.from(new Uint8Array(hashBuffer));                     // convert buffer to byte array
     // convert bytes to hex string
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}

/**
 * @async
 * @param username
 * @param password
 * @param server
 * @return {Promise<string>}
 */
const handshake = async (username: string, password: string, server: string) => {
    let time = Math.round((new Date()).getTime() / 1000);
    const key = await digestMessage(password);
    const passphrase = await digestMessage(time + key);
    console.log(passphrase);
    return new Promise((resolve, reject) => {
        axios.get(`${server}/server/json.server.php?action=handshake&user=${username}&timestamp=${time}&auth=${passphrase}&version=350001`).then(response => {

            let JSONData = response.data;

            if (JSONData.error) {
                return reject(JSONData.error);
            }
            if (JSONData.auth) {
                console.log(JSONData.auth);
                return resolve(JSONData.auth);
            }
            return reject("Something wrong with JSON");
        }).catch(error => {
            reject(error);
        });
    });
};
export default handshake;
