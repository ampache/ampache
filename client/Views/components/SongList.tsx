import React, { useContext } from 'react';
import { MusicContext } from '../../Contexts/MusicContext';
import { Song } from '../../logic/Song';
import SongRow from './SongRow';

interface SongListProps {
    songs: Song[];
}

const SongList: React.FC<SongListProps> = (props) => {
    const musicContext = useContext(MusicContext);

    const songList = props.songs.map((song: Song) => {
        return (
            <SongRow
                song={song}
                isCurrentlyPlaying={
                    musicContext.currentPlayingSong?.id === song.id
                }
                addToQueue={(next) => musicContext.addToQueue(song, next)}
                startPlaying={() =>
                    musicContext.startPlayingWithNewQueue(song, props.songs)
                }
                key={song.id}
            />
        );
    });

    return <>{songList}</>;
};

export default SongList;
