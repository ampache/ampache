import React from 'react';
import { useInfiniteFavoriteSongs } from '~logic/FavoriteSongs';
import SongList from '~components/SongList';
import Loading from 'react-loading';
import { useObserver } from '~utils/useObserver';

export const FavoritesPage = () => {
    const {
        data,
        fetchNextPage,
        hasNextPage,
        isFetching,
        isFetchingNextPage
    } = useInfiniteFavoriteSongs();

    const { observerElem } = useObserver({
        isFetching,
        hasNextPage,
        fetchNextPage
    });

    const songIds: string[] = data?.pages.reduce((acc, group) => {
        group.map((song) => acc.push(song.id));
        return acc;
    }, [] as string[]);
    if (!songIds) return <Loading />;
    return (
        <div>
            <SongList songIds={songIds} />
            <div ref={observerElem}>
                {isFetchingNextPage && hasNextPage && 'Loading...'}
            </div>
        </div>
    );
};
