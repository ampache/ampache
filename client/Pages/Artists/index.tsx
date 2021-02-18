import React, { useEffect, useState } from 'react';
import { Artist, getArtists } from '~logic/Artist';
import { User } from '~logic/User';
import AmpacheError from '~logic/AmpacheError';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import ArtistDisplayView from '~Views/ArtistDisplayView';

import style from './index.styl';

interface ArtistsPageProps {
    user: User;
}

const ArtistsPage: React.FC<ArtistsPageProps> = (props: ArtistsPageProps) => {
    const [artists, setArtists] = useState<Artist[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        getArtists(props.user.authKey)
            .then((data) => {
                setArtists(data);
            })
            .catch((error) => {
                toast.error('😞 Something went wrong getting the artist.');
                setError(error);
            });
    }, [props.user.authKey]);

    if (error) {
        return (
            <div className={style.artistsPage}>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!artists) {
        return (
            <div className={style.artistsPage}>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }
    return (
        <div className={style.artistsPage}>
            <div className={style.details}>
                {/*<div className='imageContainer'>*/}
                {/*    <img*/}
                {/*        src={this.state.theArtist.art}*/}
                {/*        alt={'Album Cover'}*/}
                {/*    />*/}
                {/*</div>*/}
                {/*Name: {this.state.theArtist.name}*/}
            </div>
            <h1>Artists</h1>
            <div className='artist-grid'>
                <ArtistDisplayView
                    artists={artists}
                    authKey={props.user.authKey}
                />
            </div>
        </div>
    );
};

export default ArtistsPage;
