import React, { useEffect, useState } from 'react';
import { Artist } from '~logic/Artist';
import ArtistDisplay from '~components/ArtistDisplay';
import { AuthKey } from '~logic/Auth';
import ReactLoading from 'react-loading';

interface ArtistDisplayViewProps {
    artists: Artist[];
    authKey: AuthKey;
}

const ArtistDisplayView: React.FC<ArtistDisplayViewProps> = (props) => {
    const [artistsState, setArtistsState] = useState(null);

    useEffect(() => {
        setArtistsState(props.artists);
    }, [props.artists]);
    if (artistsState === null) {
        return (
            <>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </>
        );
    }

    return (
        <>
            {artistsState.length === 0 && 'No results :('}

            {artistsState.map((artist) => {
                return <ArtistDisplay artist={artist} key={artist.id} />;
            })}
        </>
    );
};

export default ArtistDisplayView;
