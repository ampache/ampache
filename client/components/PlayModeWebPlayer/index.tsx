import React, { useState, useContext } from 'react';
import QueueSong from '~components/QueueBar/components/QueueSong';
import { Song } from '~logic/Song';
import { MusicContext } from '~Contexts/MusicContext';

import style from '~components/QueueBar/index.styl';

function PlayModeWebPlayer() {
    const musicContext = useContext(MusicContext);

    return(
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
    )
}

export default PlayModeWebPlayer;