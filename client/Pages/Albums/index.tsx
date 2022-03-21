import React from 'react';
import { useGetAlbums } from '~logic/Album';
import { User } from '~logic/User';
import ReactLoading from 'react-loading';
import AlbumDisplayView from '~Views/AlbumDisplayView';

import style from './index.styl';

interface AlbumsPageProps {
    user: User;
}

const AlbumsPage: React.FC<AlbumsPageProps> = (props: AlbumsPageProps) => {
    const { data: albums, error, isLoading } = useGetAlbums();

    if (error) {
        return (
            <div className={style.albumsPage}>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (isLoading) {
        return (
            <div className={style.albumsPage}>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }

    if (!albums) {
        return <div className={style.albumsPage}>No Albums</div>;
    }

    return (
        <div className={style.albumsPage}>
            <div className={style.details}>
                {/*<div className='imageContainer'>*/}
                {/*    <img*/}
                {/*        src={this.state.theAlbum.art}*/}
                {/*        alt={'Album Cover'}*/}
                {/*    />*/}
                {/*</div>*/}
                {/*Name: {this.state.theAlbum.name}*/}
            </div>
            <h1>Albums</h1>
            <div className='album-grid'>
                <AlbumDisplayView
                    albums={albums}
                    authKey={props.user.authKey}
                />
            </div>
        </div>
    );
};

export default AlbumsPage;
