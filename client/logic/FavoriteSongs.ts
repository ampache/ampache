import { ampacheClient } from '~main';
import { Song } from '~logic/Song';
import { useInfiniteQuery, useQueryClient } from '@tanstack/react-query';
import AmpacheError from '~logic/AmpacheError';

export const useInfiniteFavoriteSongs = (limit = 25) => {
    const queryClient = useQueryClient();
    return useInfiniteQuery<Song[], Error | AmpacheError>({
        queryKey: ['favoriteSongs'],
        queryFn: ({ pageParam }) => {
            return ampacheClient
                .get(``, {
                    params: {
                        action: 'advanced_search',
                        rule_1: 'favorite',
                        rule_1_operator: '0',
                        rule_1_input: '',
                        type: 'song',
                        limit,
                        offset: pageParam,
                        version: '6.0.0'
                    }
                })
                .then((response) => {
                    response.data.song.map((song) => {
                        queryClient.setQueryData(['song', song.id], song);
                    });
                    return response.data.song;
                });
        },
        getNextPageParam: (lastPage, pages) =>
            lastPage.length === limit ? pages.length * limit : undefined
    });
};
