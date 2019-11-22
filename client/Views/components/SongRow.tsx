import React from 'react';
import useContextMenu from 'react-use-context-menu';
import { Song } from '../../logic/Song';

interface SongRow {
    song: Song;
    isCurrentlyPlaying: Boolean;
    addToQueue: (next: Boolean) => void;
    startPlaying: () => void;
}

const SongRow: React.FC<SongRow> = (props) => {
    const [
        bindMenu,
        bindMenuItems,
        useContextTrigger,
        { setVisible }
    ] = useContextMenu();
    const [bindTrigger] = useContextTrigger();

    const minutes: number = Math.round(props.song.time / 60);
    const seconds: string = (props.song.time % 60).toString();
    const paddedSeconds: string =
        seconds.length === 1 ? seconds + '0' : seconds;

    return (
        <>
            <li
                className={
                    props.isCurrentlyPlaying ? 'playing songRow' : 'songRow'
                }
                {...bindTrigger}
                onClick={props.startPlaying}
            >
                <span className='title'>{props.song.title}</span>
                <span className='time'>
                    {minutes}:{paddedSeconds}
                </span>
            </li>
            <div {...bindMenu} className='contextMenu'>
                <div
                    {...bindMenuItems}
                    onClick={() => {
                        setVisible(false);
                        props.addToQueue(true);
                    }}
                >
                    Play Next
                </div>
                <div
                    {...bindMenuItems}
                    onClick={() => {
                        setVisible(false);
                        props.addToQueue(false);
                    }}
                >
                    Add to Queue
                </div>
            </div>
        </>
    );
};

export default SongRow;
