import { ampacheClient } from '~main';

const updateArt = (
    type: 'song' | 'artist' | 'album' | 'playlist' | 'search' | 'podcast',
    id: string,
    overwrite: boolean
) => {
    return ampacheClient.get(``, {
        params: {
            action: 'update_art',
            type,
            id,
            overwrite: overwrite ? 1 : 0,
            version: '6.0.0'
        }
    });
};

export default updateArt;
