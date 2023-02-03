import React, { useContext } from 'react';
import { useInfiniteFavoriteSongs } from '~logic/FavoriteSongs';
import Loading from 'react-loading';
import { useVirtualizer } from '~node_modules/@tanstack/react-virtual';
import { SongRow } from '~components/SongRow';
import { useMusicStore } from '~store';
import shallow from 'zustand/shallow';
import { MusicContext } from '~Contexts/MusicContext';

export const FavoritesPage = () => {
    const {
        data,
        status,
        error,
        fetchNextPage,
        hasNextPage,
        isFetchingNextPage
    } = useInfiniteFavoriteSongs();
    const musicContext = useContext(MusicContext);
    const { songQueue, songQueueIndex } = useMusicStore(
        (state) => ({
            songQueue: state.songQueue,
            songQueueIndex: state.songQueueIndex
        }),
        shallow
    );

    const currentPlayingSongId = songQueue[songQueueIndex];
    const handleStartPlaying = (startSongId: string) => {
        const queueIndex = songIds.findIndex((o) => o === startSongId);
        musicContext.startPlayingWithNewQueue(songIds, queueIndex);
    };

    const songIds: string[] =
        data?.pages.reduce((acc, group) => {
            group.map((song) => acc.push(song.id));
            return acc;
        }, [] as string[]) ?? [];

    const parentRef = React.useRef();

    const rowVirtualizer = useVirtualizer({
        count: hasNextPage ? songIds.length + 1 : songIds.length,
        getScrollElement: () => parentRef.current,
        estimateSize: () => 38,
        overscan: 5
    });

    React.useEffect(() => {
        const [lastItem] = [...rowVirtualizer.getVirtualItems()].reverse();

        if (!lastItem) {
            return;
        }

        if (
            lastItem.index >= songIds.length - 1 &&
            hasNextPage &&
            !isFetchingNextPage
        ) {
            fetchNextPage();
        }
    }, [
        hasNextPage,
        fetchNextPage,
        songIds.length,
        isFetchingNextPage,
        rowVirtualizer.getVirtualItems() //TODO <-- this is smelly, but i copy pasted the example. SO will have to look into it
    ]);

    if (!songIds) return <Loading />;
    return (
        <>
            {status === 'loading' ? (
                <p>Loading...</p>
            ) : status === 'error' ? (
                <span>Error: {(error as Error).message}</span>
            ) : (
                <div
                    ref={parentRef}
                    className='List'
                    style={{
                        height: `100%`,
                        width: `100%`,
                        overflow: 'auto',
                        padding: '1rem'
                    }}
                >
                    <div
                        style={{
                            height: `${rowVirtualizer.getTotalSize()}px`,
                            width: '100%',
                            position: 'relative'
                        }}
                    >
                        {rowVirtualizer.getVirtualItems().map((virtualRow) => {
                            const isLoaderRow =
                                virtualRow.index > songIds.length - 1;
                            const songId = songIds[virtualRow.index];

                            return (
                                <div
                                    key={virtualRow.index}
                                    style={{
                                        position: 'absolute',
                                        top: 0,
                                        left: 0,
                                        width: '100%',
                                        height: `${virtualRow.size}px`,
                                        transform: `translateY(${virtualRow.start}px)`
                                    }}
                                >
                                    {isLoaderRow ? (
                                        hasNextPage ? (
                                            'Loading more...'
                                        ) : (
                                            'Nothing more to load'
                                        )
                                    ) : (
                                        <SongRow
                                            songId={songId}
                                            trackNumber={(
                                                virtualRow.index + 1
                                            ).toString()}
                                            showArtist={true}
                                            showAlbum={true}
                                            startPlaying={handleStartPlaying}
                                            isCurrentlyPlaying={
                                                songId === currentPlayingSongId
                                            }
                                        />
                                    )}
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}
        </>
    );
};
