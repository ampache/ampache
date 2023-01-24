import React from 'react';
import { useGetArtists } from '~logic/Artist';
import { User } from '~logic/User';
import ReactLoading from 'react-loading';
import ArtistDisplayView from '~Views/ArtistDisplayView';

import style from './index.styl';

interface ArtistsPageProps {
    user: User;
}

const ArtistsPage: React.FC<ArtistsPageProps> = (props: ArtistsPageProps) => {
    const { data: artists, error, isLoading } = useGetArtists();

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
