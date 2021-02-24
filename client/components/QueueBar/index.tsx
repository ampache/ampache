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

    const handlePlaySong = (songID: string) => {
        const queueIndex = musicContext.songQueue.findIndex(
            (o) => o.id === songID
        );
        musicContext.changeQueuePosition(queueIndex);
    };
    const handleRemoveSong = (queueIndex: number) => {
        musicContext.removeFromQueue(queueIndex);
    };

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
                            {musicContext.songQueue.map((song: Song, index) => {
                                return (
                                    <QueueSong
                                        key={index}
                                        song={song}
                                        currentlyPlaying={
                                            musicContext.currentPlayingSong
                                                ?.id === song.id
                                        }
                                        queueIndex={index}
                                        playSong={handlePlaySong}
                                        removeSong={handleRemoveSong}
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
