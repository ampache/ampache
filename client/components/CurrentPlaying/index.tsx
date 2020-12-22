import React, { useContext } from 'react';
import { MusicContext } from '~Contexts/MusicContext';
import CDImage from '/images/icons/svg/CD.svg';
import { Link } from 'react-router-dom';
import Rating from '~components/Rating/';

import style from './index.module.styl';

const CurrentPlaying: React.FC = () => {
    const musicContext = useContext(MusicContext);

    return (
        <div className={style.currentPlaying}>
            {musicContext.currentPlayingSong == undefined && (
                <img
                    src={CDImage}
                    alt='default cover art'
                    className={style.albumArt}
                />
            )}
            {musicContext.currentPlayingSong != undefined && (
                <>
                    <Link
                        className={style.albumArt}
                        to={`/album/${musicContext.currentPlayingSong.album.id}`}
                    >
                        <img
                            src={musicContext.currentPlayingSong.art}
                            alt='cover art'
                        />
                    </Link>

                    <div className={style.info}>
                        <div className={style.songName}>
                            {musicContext.currentPlayingSong?.title}
                        </div>
                        <div className={style.albumName}>
                            <Link to={`/album/${musicContext.currentPlayingSong.album.id}`}>{musicContext.currentPlayingSong?.album.name}</Link>
                        </div>
                        <div className={style.artistName}>
                            <Link to={`/artist/${musicContext.currentPlayingSong.artist.id}`}>{musicContext.currentPlayingSong?.artist.name}</Link>
                        </div>
                        <div className='ratingBarContainer'>
                            <div className='ratingBar'>
                                <Rating />
                            </div>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
};

export default CurrentPlaying;
