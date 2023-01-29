import * as style from './index.styl';
import { useMusicStore } from '~store';
import shallow from 'zustand/shallow';

const formatLabel = (s) => [
    (s - (s %= 60)) / 60 + (9 < s ? ':' : ':0') + s
    //https://stackoverflow.com/a/37770048
];
export const SongTime = ({ endTime = 0 }: { endTime: number }) => {
    const { songPosition } = useMusicStore(
        (state) => ({
            songPosition: state.songPosition
        }),
        shallow
    );

    return (
        <div className={style.seekTimes}>
            <span>{formatLabel(songPosition)}</span>
            <span className={style.seekEnd}>{formatLabel(endTime)}</span>
        </div>
    );
};
