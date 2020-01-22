import React, { useCallback, useEffect, useState } from 'react';
import { PLAYERSTATUS } from '../enum/PlayerStatus';
import { useHotkeys } from 'react-hotkeys-hook';
import { AuthKey } from '../logic/Auth';
import { Song } from '../logic/Song';
import ReactAudioPlayer from 'react-audio-player';

interface MusicContextProps {
    authKey: AuthKey;
}

interface MusicContext {
    playerStatus: PLAYERSTATUS;
    currentPlayingSong: Song;
    songPosition: number;
    songQueueIndex: number;
    songQueue: Song[];
    playPause: () => void;
    playPrevious: () => void;
    playNext: () => void;
    startPlayingWithNewQueue: (song: Song, newQueue: Song[]) => {};
    addToQueue: (song: Song, next: boolean) => void;
}

export const MusicContext = React.createContext({
    playerStatus: PLAYERSTATUS.STOPPED
} as MusicContext);

export const MusicContextProvider: React.FC<MusicContextProps> = (props) => {
    const [playerStatus, setPlayerStatus] = useState(PLAYERSTATUS.STOPPED);
    const [currentPlayingSong, setCurrentPlayingSong]: [
        Song,
        React.Dispatch<React.SetStateAction<Song>>
    ] = useState();
    const [songQueue, setSongQueue]: [
        Song[],
        React.Dispatch<React.SetStateAction<Song[]>>
    ] = useState([]);
    const [songPosition, setSongPosition] = useState(-1);
    const [songQueueIndex, setSongQueueIndex] = useState(-1);
    const [userQCount, setUserQCount] = useState(0);

    let audioRef = undefined;

    useHotkeys(
        'space',
        (e) => {
            e.preventDefault();
            playPause();
        },
        [playerStatus]
    );

    const playPause = useCallback(() => {
        if (currentPlayingSong == undefined) return;
        const isPaused = audioRef.audioEl?.paused;
        if (isPaused) {
            audioRef.audioEl.play();
            return;
        } else {
            audioRef.audioEl.pause();
        }
    }, [audioRef, currentPlayingSong]);

    const _playSong = useCallback(
        async (song: Song) => {
            if (!song) {
                return console.error(
                    'Playing an undefined song, something is wrong.'
                );
            }
            console.log(audioRef);
            audioRef.audioEl.src = song.url;
            audioRef.audioEl.title = `${song.artist.name} - ${song.title}`;
            audioRef.audioEl.play();
            setCurrentPlayingSong(song);
            if ('mediaSession' in navigator) {
                navigator.mediaSession.metadata = new MediaMetadata({
                    title: song.title,
                    artist: song.artist.name,
                    album: song.album.name,
                    artwork: [{ src: song.art }]
                });

                navigator.mediaSession.setActionHandler('play', () => {
                    playPause();
                });
                navigator.mediaSession.setActionHandler('pause', () => {
                    playPause();
                });
            }
        },
        [audioRef, playPause]
    );

    const playPrevious = useCallback(() => {
        const previousSong = songQueue[songQueueIndex - 1];
        setSongQueueIndex(songQueueIndex - 1);
        _playSong(previousSong);
    }, [_playSong, songQueue, songQueueIndex]);

    const playNext = useCallback(() => {
        console.log(songQueueIndex);
        const nextSong = songQueue[songQueueIndex + 1];
        setSongQueueIndex(songQueueIndex + 1);
        if (userQCount > 0) setUserQCount(userQCount - 1);
        _playSong(nextSong);
    }, [_playSong, songQueue, songQueueIndex, userQCount]);

    useEffect(() => {
        if (!currentPlayingSong) return;
        //This seems to work, but there is a slight delay if you spam next or previous.
        //Don't know if there is a better way.
        if ('mediaSession' in navigator) {
            navigator.mediaSession.setActionHandler('nexttrack', () => {
                playNext();
            });
            navigator.mediaSession.setActionHandler('previoustrack', () => {
                playPrevious();
            });
        }
    }, [currentPlayingSong, playNext, playPrevious]);

    const songIsOver = () => {
        if (songQueueIndex === songQueue.length - 1) {
            setCurrentPlayingSong({} as Song);
            setPlayerStatus(PLAYERSTATUS.STOPPED);
            return;
        }
        playNext();
    };

    const startPlayingWithNewQueue = async (song: Song, newQueue: Song[]) => {
        if (song.id === currentPlayingSong?.id) return;

        const queueIndex = newQueue.findIndex((o) => o.id === song.id);

        setSongQueue(newQueue);
        setSongQueueIndex(queueIndex); // TODO Reduce these two sets?
        _playSong(song);
    };

    const addToQueue = (song: Song, next: boolean) => {
        const newQueue = [...songQueue];
        console.log('ADD', userQCount);
        if (next) {
            //splice starts at 1, so we don't need +2 //TODO make this comment more clear!
            newQueue.splice(songQueueIndex + 1 + userQCount, 0, song);
            setUserQCount(userQCount + 1); //TODO: Reducer?

            setSongQueue(newQueue);
            return;
        }

        newQueue.push(song);
        setSongQueue(newQueue);
    };

    const durationChange = (elapsed) => {
        const songTime = currentPlayingSong.time;
        const percent = (elapsed / songTime) * 100;
        setSongPosition(percent);
    };

    return (
        <MusicContext.Provider //TODO: Should this provider be split into multiple providers?
            value={{
                playerStatus,
                currentPlayingSong,
                songQueueIndex,
                songQueue,
                songPosition, //TODO: Performance concern?
                playPause,
                playPrevious,
                playNext,
                startPlayingWithNewQueue,
                addToQueue
            }}
        >
            <ReactAudioPlayer //TODO: If this doesn't get updated soon, remove it.
                ref={(element) => {
                    if (element == undefined) return;
                    audioRef = element;
                }}
                listenInterval={1000}
                onListen={durationChange}
                onEnded={songIsOver}
                onPause={() => {
                    setPlayerStatus(PLAYERSTATUS.PAUSED);
                }}
                onPlay={() => {
                    setPlayerStatus(PLAYERSTATUS.PLAYING);
                }}
            />
            {props.children}
        </MusicContext.Provider>
    );
};
