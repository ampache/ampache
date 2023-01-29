import React from 'react';
import { useGetArtists } from '~logic/Artist';
import ReactLoading from 'react-loading';

import ArtistDisplay from '~components/ArtistDisplay';

const ArtistsPage = () => {
    const { data: artists, error } = useGetArtists();

    if (error) {
        return (
            <div>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!artists) {
        return (
            <div>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }
    return (
        <div>
            <h1>Artists</h1>
            <div className='artist-grid'>
                {artists.map((artist) => (
                    <ArtistDisplay artistID={artist.id} key={artist.id} />
                ))}
            </div>
        </div>
    );
};

export default ArtistsPage;
