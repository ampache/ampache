import React, { useContext } from 'react';
import { MusicContext } from '~Contexts/MusicContext';
import { Link } from 'react-router-dom';

import style from './index.styl';

const CurrentPlaying: React.FC = () => {
    const musicContext = useContext(MusicContext);

    return (
        <div className={style.currentPlaying}>
            {musicContext.currentPlayingSong != undefined && (
                <div className={style.info}>
                    <div className={style.songName}>
                        {musicContext.currentPlayingSong?.title}
                    </div>
                    <div className={style.albumName}>
                        <Link
                            to={`/album/${musicContext.currentPlayingSong.album.id}`}
                        >
                            {musicContext.currentPlayingSong?.album.name}
                        </Link>
                    </div>
                    <div className={style.artistName}>
                        <Link
                            to={`/artist/${musicContext.currentPlayingSong.artist.id}`}
                        >
                            {musicContext.currentPlayingSong?.artist.name}
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
};

export default CurrentPlaying;
