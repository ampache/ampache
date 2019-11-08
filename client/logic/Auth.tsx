import axios from "axios";


async function digestMessage(message) {
    const msgUint8 = new TextEncoder().encode(message);                           // encode as (utf-8) Uint8Array
    const hashBuffer = await crypto.subtle.digest('SHA-256', msgUint8);           // hash the message
    const hashArray = Array.from(new Uint8Array(hashBuffer));                     // convert buffer to byte array
     // convert bytes to hex string
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
}

/**
 * @callback handshakeSongsCallback
 * @param {null|string} errorCode - The code returned by the Ampache server
 * @param {string|null} authKey - Key used for all future interactions with the API
 */
const handshake = async (username, password, server) => {
    let time = Math.round((new Date()).getTime() / 1000);
    const key = await digestMessage(password);
    const passphrase = await digestMessage(time + key);
    console.log(passphrase);
    return new Promise((resolve, reject) => {
        axios.get(`${server}/server/json.server.php?action=handshake&user=${username}&timestamp=${time}&auth=${passphrase}&version=350001`).then(response => {

            let JSONData = response.data;

            if (JSONData.error) {
                reject(JSONData.error);
            } else if (JSONData.auth) {
                console.log(JSONData.auth);
                resolve(JSONData.auth);
            } else {
                reject("Something wrong with JSON");
            }
        }).catch(error => {
            reject(error);
        });
    });
};
export default handshake;
