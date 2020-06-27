import { AuthKey } from './Auth';
import axios from 'axios';
import AmpacheError from './AmpacheError';

enum CatalogTask {
    add_to_catalog = 'add_to_catalog',
    clean_catalog = 'clean_catalog'
}

const catalogAction = (
    catalogID: number,
    task: CatalogTask,
    authKey: AuthKey
) => {
    return axios
        .get(
            `${process.env.ServerURL}/server/json.server.php?action=catalog_action&task=${task}&catalog=${catalogID}&auth=${authKey}&version=400001`
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

export const updateCatalog = (catalogID: number, authKey: AuthKey) => {
    return catalogAction(catalogID, CatalogTask.add_to_catalog, authKey);
};

export const cleanCatalog = (catalogID: number, authKey: AuthKey) => {
    return catalogAction(catalogID, CatalogTask.clean_catalog, authKey);
};
