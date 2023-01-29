import React from 'react';
import CDImage from '/images/icons/svg/CD.svg';
import { Link } from 'react-router-dom';

import * as style from './index.styl';
import { useMusicStore } from '~store';
import { useGetSong } from '~logic/Song';
import shallow from '~node_modules/zustand/shallow';

const CurrentPlayingArt: React.FC = () => {
    const { songQueue, songQueueIndex } = useMusicStore(
        (state) => ({
            songQueue: state.songQueue,
            songQueueIndex: state.songQueueIndex
        }),
        shallow
    );

    const currentPlayingSongId = songQueue[songQueueIndex];

    const { data: currentPlayingSong } = useGetSong(currentPlayingSongId, {
        enabled: false
    });

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
