import React from 'react';
import { useInfiniteArtists } from '~logic/Artist';
import ReactLoading from 'react-loading';

import ArtistDisplay from '~components/ArtistDisplay';
import { useObserver } from '~utils/useObserver';

const ArtistsPage = () => {
    const {
        data,
        fetchNextPage,
        hasNextPage,
        isFetching,
        isFetchingNextPage
    } = useInfiniteArtists(25);

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
            <h1>Artists</h1>
            <div className='artist-grid'>
                {data.pages.map((group, i) => (
                    <React.Fragment key={i}>
                        {group.map((artist) => (
                            <ArtistDisplay
                                artistID={artist.id}
                                key={artist.id}
                            />
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

export default ArtistsPage;
