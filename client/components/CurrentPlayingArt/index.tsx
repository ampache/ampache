import React, { useContext } from 'react';
import { MusicContext } from '~Contexts/MusicContext';
import CDImage from '/images/icons/svg/CD.svg';
import { Link } from 'react-router-dom';

import style from './index.styl';

const CurrentPlayingArt: React.FC = () => {
    const musicContext = useContext(MusicContext);

    return (
        <div className={style.currentPlayingArt}>
            {musicContext.currentPlayingSong == undefined && (
                <img
                    src={CDImage}
                    alt='default cover art'
                    className={style.albumArt}
                />
            )}
            {musicContext.currentPlayingSong != undefined && (
                <Link
                    className={style.albumArt}
                    to={`/album/${musicContext.currentPlayingSong.album.id}`}
                >
                    <img
                        src={musicContext.currentPlayingSong.art}
                        alt='cover art'
                    />
                </Link>
            )}
        </div>
    );
};

export default CurrentPlayingArt;
