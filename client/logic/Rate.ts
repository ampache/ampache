import { ampacheClient } from '~main';
import { ItemType } from '~types';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { Song } from '~logic/Song';
import { Album } from '~logic/Album';
import { Playlist } from '~logic/Playlist';
import { Artist } from '~logic/Artist';

const rateItemFn = ({
    itemId,
    type,
    rating
}: {
    itemId: string;
    type: ItemType;
    rating: number;
}) =>
    ampacheClient.get('', {
        params: {
            action: 'rate',
            type,
            id: itemId,
            rating,
            version: '6.0.0'
        }
    });

export const useRateItem = () => {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: rateItemFn,
        onMutate: ({ rating, itemId, type }) => {
            queryClient.setQueriesData<Song | Album | Artist | Playlist>(
                [type, itemId],
                (input) => ({
                    ...input,
                    rating
                })
            );
        },
        onSettled: (_, _err, { itemId, type }) => {
            queryClient.invalidateQueries([type, itemId]);
        }
    });
};
