import React from 'react';
import CDImage from '/images/icons/svg/CD.svg';
import { Link } from 'react-router-dom';

import style from './index.styl';
import { useStore } from '~store';

const CurrentPlayingArt: React.FC = () => {
    const currentPlayingSong = useStore().currentPlayingSong;
    return (
        <div className={style.currentPlayingArt}>
            {!currentPlayingSong && (
                <img
                    src={CDImage}
                    alt='default cover art'
                    className={style.albumArt}
                />
            )}
            {currentPlayingSong && (
                <Link
                    className={style.albumArt}
                    to={`/album/${currentPlayingSong.album.id}`}
                >
                    <img src={currentPlayingSong.art} alt='cover art' />
                </Link>
            )}
        </div>
    );
};

export default CurrentPlayingArt;
