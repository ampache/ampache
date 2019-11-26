import React, { useEffect, useState } from 'react';
import { Artist, getArtists } from '../../logic/Artist';
import { User } from '../../logic/User';
import AmpacheError from '../../logic/AmpacheError';
import ArtistDisplay from '../components/ArtistDisplay';

interface ArtistsViewProps {
    user: User;
}

const ArtistsView: React.FC<ArtistsViewProps> = (props) => {
    const [artists, setArtists] = useState<Artist[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        getArtists(props.user.authKey)
            .then((data) => {
                setArtists(data);
            })
            .catch((error) => {
                setError(error);
            });
    }, []);

    if (error) {
        return (
            <div className='artistsPage'>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!artists) {
        return (
            <div className='artistsPage'>
                <span>Loading...</span>
            </div>
        );
    }
    return (
        <div className='artistsPage'>
            <div className='details'>
                {/*<div className='imageContainer'>*/}
                {/*    <img*/}
                {/*        src={this.state.theArtist.art}*/}
                {/*        alt={'Album Cover'}*/}
                {/*    />*/}
                {/*</div>*/}
                {/*Name: {this.state.theArtist.name}*/}
            </div>
            <h1>Artists</h1>
            <div className='artists'>
                {artists.map((theArtist) => {
                    return (
                        <ArtistDisplay artist={theArtist} key={theArtist.id} />
                    );
                })}
            </div>
        </div>
    );
};

export default ArtistsView;
