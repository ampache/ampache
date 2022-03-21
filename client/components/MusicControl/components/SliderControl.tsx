import style from '~components/MusicControl/index.styl';
import Slider from '~node_modules/@material-ui/core/Slider';
import React, { useContext, useState } from 'react';
import { MusicContext } from '~Contexts/MusicContext';
import { useStore } from '~store';

const SliderControl = () => {
    const musicContext = useContext(MusicContext);
    const { currentPlayingSong } = useStore();

    const [isSeeking, setIsSeeking] = useState(false);
    const [seekPosition, setSeekPosition] = useState(-1);
    return (
        <div className={style.seekbar}>
            <Slider
                min={0}
                max={currentPlayingSong?.time ?? 0}
                value={isSeeking ? seekPosition : 0}
                // value={isSeeking ? seekPosition : musicContext.songPosition}
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
