import React from 'react';
import SVG from 'react-inlinesvg';
import * as style from './index.styl';
import { useGetSong } from '~logic/Song';
import Loading from 'react-loading';

interface QueueSongProps {
    songId: string;
    currentlyPlaying: boolean;
    queueIndex: number;
    playSong: (songID: string) => void;
    removeSong: (queueIndex: number) => void;
}

const QueueSong: React.FC<QueueSongProps> = (props) => {
    const { playSong, songId } = props;

    const { data: song, refetch: fetchSong, isFetching } = useGetSong(songId, {
        enabled: false
    });

    if (!song) {
        if (!isFetching) {
            fetchSong();
        }
        return (
            <li
                className={`${style.song} card-clear`}
                onClick={() => playSong(songId)}
            >
                <Loading />
            </li>
        );
    }
    return (
        <li
            className={
                props.currentlyPlaying
                    ? `${style.song} nowPlaying card-clear`
                    : `${style.song} card-clear`
            }
            onClick={() => playSong(songId)}
        >
            <div className={style.imageWrapper}>
                <img src={song.art} alt='Album cover' />
            </div>
            <div className={style.details}>
                <div className={`card-title ${style.songName}`}>
                    {song.title}
                </div>
                <div className={style.albumArtist}>{song.artist.name}</div>
            </div>
            {!props.currentlyPlaying && (
                <div className={style.actions}>
                    <SVG
                        className='icon icon-button-smallest'
                        src={require('~images/icons/svg/cross.svg')}
                        title='Remove'
                        description='Remove song from Queue'
                        role='button'
                        onClick={(e) => {
                            e.stopPropagation();
                            console.log(props.queueIndex);
                            props.removeSong(props.queueIndex);
                        }}
                    />
                </div>
            )}
        </li>
    );
};

export default QueueSong;
