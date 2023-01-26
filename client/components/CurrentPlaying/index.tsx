import React from 'react';
import { Link } from 'react-router-dom';

import style from './index.styl';
import { useMusicStore } from '~store';
import { useGetSong } from '~logic/Song';
import shallow from '~node_modules/zustand/shallow';

const CurrentPlaying = () => {
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
        <div className={style.currentPlaying}>
            {currentPlayingSong && (
                <div className={style.info}>
                    <div className={style.songName}>
                        {currentPlayingSong.title}
                    </div>
                    <div className={style.albumName}>
                        <Link to={`/album/${currentPlayingSong.album.id}`}>
                            {currentPlayingSong.album.name}
                        </Link>
                    </div>
                    <div className={style.artistName}>
                        <Link to={`/artist/${currentPlayingSong.artist.id}`}>
                            {currentPlayingSong.artist.name}
                        </Link>
                    </div>
                </div>
            )}
        </div>
    );
};

export default CurrentPlaying;
