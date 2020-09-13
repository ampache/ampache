import React, { useContext } from 'react';
import { Song } from '~logic/Song';
import { MusicContext } from '~Contexts/MusicContext';
import QueueSong from './components/QueueSong';

import style from './index.module.styl';

interface QueueBarProps {
    visible: boolean;
}

const QueueBar: React.FC<QueueBarProps> = (props) => {
    const musicContext = useContext(MusicContext);

    return (
        <div
            className={
                props.visible
                    ? `${style.sidebar} ${style.queueBar} ${style.visible}`
                    : `${style.sidebar} ${style.queueBar}`
            }
        >
            <div className={style.title}>Your Queue</div>
            <ul className={style.songs}>
                {musicContext.songQueue.length == 0 && (
                    <div className={style.emptyQueue}>Nothing in the queue</div>
                )}
                {musicContext.songQueue.map((song: Song) => {
                    return (
                        <QueueSong
                            key={song.id}
                            song={song}
                            currentlyPlaying={
                                musicContext.currentPlayingSong?.id === song.id
                            }
                            onClick={() => {
                                musicContext.startPlayingWithNewQueue(
                                    song,
                                    musicContext.songQueue
                                );
                            }}
                        />
                    );
                })}
            </ul>
        </div>
    );
};

export default QueueBar;
