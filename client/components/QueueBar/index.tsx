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
} from "react-device-detect";

interface QueueBarProps {
    visible: boolean;
    setQueueBarVisibility: (visible: boolean) => void;
}

const QueueBar: React.FC<QueueBarProps> = (props) => {
    const musicContext = useContext(MusicContext);

    const [{ x, width }, set] = useSpring(() => ({
        x: 0,
        width: 0
    }));

    set({ width: props.visible ? 300 : 0 });

    const bind = useDrag(({ down, movement: [mx, my] }) => {

        console.log(mx)
        if (mx >= 150 && !down) {
            props.setQueueBarVisibility(false);
            set({ x: 0, width: 0 });
            return;
        }

        if (mx < 0) {
            return; //We don't want to allow it to be pulled left
        }
        if (down) {
            set({ x: mx });
        } else {
            set({ x: 0, width: 300 });
        }
    });

    return (
            <animated.div
                {...bind()}
                style={{ x, width }}
                className={
                    props.visible
                        ? `${style.queueBar} ${style.visible}`
                        : `${style.queueBar} ${style.hidden}`
                }
            >
                <div className={style.title}>Now playing</div>
                <ul className={style.songs}>
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
                                    musicContext.currentPlayingSong?.id ===
                                    song.id
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
            </animated.div>
    );
};

export default QueueBar;
