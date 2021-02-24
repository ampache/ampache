import React, { useEffect, useState } from 'react';
import { Artist, flagArtist } from '~logic/Artist';
import ArtistDisplay from '~components/ArtistDisplay';
import { AuthKey } from '~logic/Auth';
import { toast } from 'react-toastify';
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

    const handleFlagArtist = (artistID: string, favorite: boolean) => {
        flagArtist(artistID, favorite, props.authKey)
            .then(() => {
                const newArtists = artistsState.map((artist) => {
                    if (artist.id === artistID) {
                        return { ...artist, flag: favorite };
                    }
                    return artist;
                });
                setArtistsState(newArtists);
                if (favorite) {
                    return toast.success('Artist added to favorites');
                }
                toast.success('Artist removed from favorites');
            })
            .catch(() => {
                if (favorite) {
                    toast.error(
                        '😞 Something went wrong adding artist to favorites.'
                    );
                } else {
                    toast.error(
                        '😞 Something went wrong removing artist from favorites.'
                    );
                }
            });
    };

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
                return (
                    <ArtistDisplay
                        artist={artist}
                        flagArtist={handleFlagArtist}
                        key={artist.id}
                    />
                );
            })}
        </>
    );
};

export default ArtistDisplayView;
