import React, { useContext } from 'react';
import { Song } from '../../../logic/Song';
import { MusicContext } from '../../../Contexts/MusicContext';
import QueueSong from './components/QueueSong';

interface QueueBarProps {
    visible: boolean;
}

const QueueBar: React.FC<QueueBarProps> = (props) => {
    const musicContext = useContext(MusicContext);

    return (
        <div
            className={
                props.visible ? 'sidebar queueBar visible' : 'sidebar queueBar'
            }
        >
            <div className='title'>Your Queue</div>
            <ul className='songs'>
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
