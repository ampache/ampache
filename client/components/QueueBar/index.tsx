import React, { useContext } from 'react';
import { Song } from '~logic/Song';
import { MusicContext } from '~Contexts/MusicContext';
import QueueSong from './components/QueueSong';

import style from './index.styl';
import { useSpring, animated } from 'react-spring';

interface QueueBarProps {
    visible: boolean;
    setQueueBarVisibility: (visible: boolean) => void;
}

const QueueBar: React.FC<QueueBarProps> = (props) => {
    const musicContext = useContext(MusicContext);

    const queueBarStart = '100%';
    const queueBarEnd = '0%';

    const [{ x }, set] = useSpring(() => ({
        x: queueBarStart,
        from: { x: queueBarStart }
    }));

    set({ x: props.visible ? queueBarEnd : queueBarStart });

    return (
        <>
            <animated.div
                style={{ x }}
                className={
                    props.visible
                        ? `${style.queueBar} ${style.visible}`
                        : `${style.queueBar} ${style.hidden}`
                }
            >
                <h4 className={style.title}>Now playing</h4>
                <div className={style.queueList}>
                    <div className={style.queueListInner}>
                        <ul className={`striped-list ${style.songs}`}>
                            {musicContext.songQueue.length == 0 && (
                                <div className={style.emptyQueue}>
                                    Nothing in the queue
                                </div>
                            )}
                            {musicContext.songQueue.map((song: Song) => {
                                return (
                                    <QueueSong
                                        key={song.id}
                                        song={song}
                                        currentlyPlaying={
                                            musicContext.currentPlayingSong
                                                ?.id === song.id
                                        }
                                        onClick={() => {
                                            const queueIndex = musicContext.songQueue.findIndex(
                                                (o) => o.id === song.id
                                            );
                                            musicContext.startPlayingWithNewQueue(
                                                musicContext.songQueue,
                                                queueIndex
                                            );
                                        }}
                                    />
                                );
                            })}
                        </ul>
                    </div>
                </div>
            </animated.div>
            <div
                className={style.backdrop}
                onClick={() => props.setQueueBarVisibility(false)}
            />
        </>
    );
};

export default QueueBar;
