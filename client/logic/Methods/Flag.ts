import { ampacheClient } from '~main';
import { useMutation, useQueryClient } from 'react-query';
import { toast } from 'react-toastify';
import { useStore } from '~store';
import { ItemType } from '~types';

type FlagInput = {
    type: ItemType;
    objectID: string;
    favorite: boolean;
};

const query = ({ type, favorite, objectID }: FlagInput) => {
    return ampacheClient.get(``, {
        params: {
            action: 'flag',
            type,
            id: objectID,
            flag: Number(favorite),
            version: 400001
        }
    });
};

export const useFlagItem = (type: ItemType, id: string) => {
    const queryClient = useQueryClient();
    const { flagCurrentSong, currentPlayingSong } = useStore();

    return useMutation(
        (input: FlagInput) => {
            return query(input);
        },
        {
            onSuccess: (data, variables) => {
                queryClient.invalidateQueries([type, id]);
                if (currentPlayingSong?.id === variables.objectID) {
                    flagCurrentSong(variables.favorite);
                }
                if (variables.favorite) {
                    toast.success(`${variables.type} added to favorites`);
                    return;
                }
                toast.success(`${variables.type} removed from favorites`);
            },
            onError: (_, variables) => {
                toast.error(
                    `Something went wrong favouring the ${variables.type}`
                );
            }
        }
    );
};
