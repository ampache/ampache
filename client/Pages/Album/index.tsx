import React, { memo } from 'react';
import { useGetAlbum } from '~logic/Album';
import { Link, useParams } from 'react-router-dom';
import ReactLoading from 'react-loading';
import SimpleRating from '~components/SimpleRating';

import * as style from './index.styl';
import SongList from '~components/SongList';

const AlbumPage = memo(() => {
    const { albumID } = useParams();

    const { data: album, error } = useGetAlbum({
        albumID,
        includeSongs: true
    });

    if (error) {
        return (
            <div>
                <span>Error: {error.message}</span>
            </div>
        );
    }
    if (!album) {
        return (
            <div>
                <ReactLoading color='#FF9D00' type={'bubbles'} />
            </div>
        );
    }
    const songIds = album.tracks.map((song) => song.id);
    return (
        <div className={'paddedPage'}>
            <div className={style.album}>
                <div className={style.imageContainer}>
                    <img src={album.art} alt={'Album Cover'} />
                </div>
                <div className={style.details}>
                    <div className={style.rating}>
                        <SimpleRating
                            value={album.rating}
                            fav={album.flag}
                            itemId={album.id}
                            type='album'
                        />
                    </div>
                    <div className={`card-title ${style.albumName}`}>
                        {album.name}
                    </div>
                    <div>
                        <Link to={`/artist/${album.artist.id}`}>
                            {album.artist.name}
                        </Link>
                    </div>
                    <div>
                        {album.year} - {album.tracks.length} tracks
                    </div>
                </div>
            </div>
            <div className={style.songs}>
                <SongList songIds={songIds} />
            </div>
        </div>
    );
});

export default AlbumPage;
