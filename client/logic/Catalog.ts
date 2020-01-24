import { AuthKey } from './Auth';
import axios from 'axios';
import AmpacheError from './AmpacheError';

export const updateCatalog = (catalogID: number, authKey: AuthKey) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=catalog_action&task=add_to_catalog&catalog=${catalogID}&auth=${authKey}&version=400001`
        )
        .then((response) => {
            const JSONData = response.data;
            if (!JSONData) {
                throw new Error('Server Error');
            }
            if (JSONData.error) {
                throw new AmpacheError(JSONData.error);
            }
            console.log(JSONData);
            return true;
        });
};
