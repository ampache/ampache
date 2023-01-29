import * as style from '~components/MusicControl/index.styl';
import { Slider } from '@mui/material';
import React, { useContext, useState } from 'react';
import { MusicContext } from '~Contexts/MusicContext';
import { useMusicStore } from '~store';
import { useGetSong } from '~logic/Song';
import shallow from '~node_modules/zustand/shallow';

const SliderControl = () => {
    const musicContext = useContext(MusicContext);
    const { songQueue, songQueueIndex, songPosition } = useMusicStore(
        (state) => ({
            songQueue: state.songQueue,
            songQueueIndex: state.songQueueIndex,
            songPosition: state.songPosition
        }),
        shallow
    );

    const currentPlayingSongId = songQueue[songQueueIndex];

    const { data: currentPlayingSong } = useGetSong(currentPlayingSongId, {
        enabled: false
    });

    const [isSeeking, setIsSeeking] = useState(false);
    const [seekPosition, setSeekPosition] = useState(-1);
    return (
        <div className={style.seekbar}>
            <Slider
                min={0}
                max={currentPlayingSong?.time ?? 0}
                value={isSeeking ? seekPosition : songPosition}
                onChangeCommitted={(_, value: number) => {
                    // setIsSeeking(true);
                    // setValue(value);
                    // setSeekPosition(value);
                    musicContext.seekSongTo(value);
                    setIsSeeking(false);
                }}
                onChange={(_, value: number) => {
                    setIsSeeking(true);
                    setSeekPosition(value);
                }}
                disabled={currentPlayingSong == undefined}
                aria-labelledby='continuous-slider'
            />
        </div>
    );
};

export default SliderControl;
