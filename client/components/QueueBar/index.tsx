import React, { useContext } from 'react';
import { Song } from '~logic/Song';
import { MusicContext } from '~Contexts/MusicContext';
import QueueSong from './components/QueueSong';

import style from './index.styl';
import { useDrag } from 'react-use-gesture';
import { useSpring, useTransition, animated } from 'react-spring';
import { RemoveScroll } from 'react-remove-scroll';
import {
    BrowserView,
    MobileView,
    isBrowser,
    isMobile
} from 'react-device-detect';

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
