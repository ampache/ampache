import React from 'react';
import { useInfiniteAlbums } from '~logic/Album';
import ReactLoading from 'react-loading';

import AlbumDisplay from '~components/AlbumDisplay';
import { useObserver } from '~utils/useObserver';

const AlbumsPage = () => {
    const {
        data,
        fetchNextPage,
        hasNextPage,
        isFetching,
        isFetchingNextPage
    } = useInfiniteAlbums(25);

    const { observerElem } = useObserver({
        isFetching,
        hasNextPage,
        fetchNextPage
    });

    if (!data) {
        return (
            <div>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }
    return (
        <div className={'paddedPage'}>
            <h1>Albums</h1>

            <div className='album-grid'>
                {data.pages.map((group, i) => (
                    <React.Fragment key={i}>
                        {group.map((album) => (
                            <AlbumDisplay albumId={album.id} key={album.id} />
                        ))}
                    </React.Fragment>
                ))}
            </div>
            <div ref={observerElem}>
                {isFetchingNextPage && hasNextPage && 'Loading...'}
            </div>
        </div>
    );
};

export default AlbumsPage;
