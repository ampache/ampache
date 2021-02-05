import React, { useContext } from 'react';
import { MusicContext } from '~Contexts/MusicContext';
import PlayModeSelector from "~components/PlayModeSelector";
import PlayModeWebPlayer from "~components/PlayModeWebPlayer";

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
                <PlayModeSelector />
                <div className={style.queueList}>
                    <div className={style.queueListInner}>
                        <PlayModeWebPlayer />
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
