import React, { useEffect, useState } from 'react';
import { Album, getAlbums } from '~logic/Album';
import { User } from '~logic/User';
import AmpacheError from '~logic/AmpacheError';
import ReactLoading from 'react-loading';
import { toast } from 'react-toastify';
import AlbumDisplayView from '~Views/AlbumDisplayView';

import style from './index.styl';

interface AlbumsPageProps {
    user: User;
}

const AlbumsPage: React.FC<AlbumsPageProps> = (props: AlbumsPageProps) => {
    const [albums, setAlbums] = useState<Album[]>(null);
    const [error, setError] = useState<Error | AmpacheError>(null);

    useEffect(() => {
        getAlbums(props.user.authKey)
            .then((data) => {
                console.log(data);
                setAlbums(data);
            })
            .catch((error) => {
                toast.error('😞 Something went wrong getting the album.');
                setError(error);
            });
    }, [props.user.authKey]);

    if (error) {
        return (
            <div className={style.albumsPage}>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!albums) {
        return (
            <div className={style.albumsPage}>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
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
