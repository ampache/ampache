import React, { useCallback, useEffect, useRef, useState } from 'react';
import { PLAYERSTATUS } from '~enum/PlayerStatus';
import { useHotkeys } from 'react-hotkeys-hook';
import { Song } from '~logic/Song';
import ReactAudioPlayer from 'react-audio-player';

interface MusicContext {
    playerStatus: PLAYERSTATUS;
    currentPlayingSong: Song;
    songPosition: number;
    volume: number;
    songQueueIndex: number;
    songQueue: Song[];
    playPause: () => void;
    playPrevious: () => void;
    playNext: () => void;
    toggleFlag: () => void;
    startPlayingWithNewQueue: (newQueue: Song[], startPosition?) => void;
    addToQueue: (song: Song, next: boolean) => void;
    removeFromQueue: (queueIndex: number) => void;
    seekSongTo: (newPosition: number) => void;
    setVolume: (newVolume: number) => void;
}

export const MusicContext = React.createContext({
    playerStatus: PLAYERSTATUS.STOPPED
} as MusicContext);

export const MusicContextProvider: React.FC = (props) => {
    const [playerStatus, setPlayerStatus] = useState(PLAYERSTATUS.STOPPED);
    const currentPlayingSongRef: React.MutableRefObject<Song> = useRef(null);
    const [songQueue, setSongQueue]: [
        Song[],
        React.Dispatch<React.SetStateAction<Song[]>>
    ] = useState([]);
    const [songPosition, setSongPosition] = useState(0);
    const [songQueueIndex, setSongQueueIndex] = useState(-1);
    const [userQCount, setUserQCount] = useState(0);
    const [volume, setVolume] = useState(100);

    let audioRef = undefined; //TODO: Should this be useRef?

    useHotkeys(
        'space',
        (e) => {
            e.preventDefault();
            playPause();
        },
        {},
        [playerStatus]
    );

    const playPause = useCallback(() => {
        if (currentPlayingSongRef.current == undefined) return;

        const isPaused = audioRef.audioEl?.current.paused;
        if (isPaused) {
            audioRef.audioEl.current.play();
            return;
        } else {
            audioRef.audioEl.current.pause();
        }
    }, [audioRef, currentPlayingSongRef]);

    const _playSong = useCallback(
        async (song: Song) => {
            if (!song) {
                return console.error(
                    'Playing an undefined song, something is wrong.'
                );
            }
            console.log(audioRef.audioEl);
            audioRef.audioEl.current.src = song.url;
            audioRef.audioEl.current.title = `${song.artist.name} - ${song.title}`;
            audioRef.audioEl.current.play();
            currentPlayingSongRef.current = song;
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

    const toggleFlag = () => {
        currentPlayingSongRef.current.flag = !currentPlayingSongRef.current
            .flag;
    };

    useEffect(() => {
        if (!currentPlayingSongRef) return;
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
    }, [currentPlayingSongRef, playNext, playPrevious]);

    const songIsOver = () => {
        if (songQueueIndex === songQueue.length - 1) {
            currentPlayingSongRef.current = null;
            setPlayerStatus(PLAYERSTATUS.STOPPED);
            return;
        }
        playNext();
    };

    const startPlayingWithNewQueue = async (
        newQueue: Song[],
        startPosition = 0
    ) => {
        if (newQueue[startPosition].id === currentPlayingSongRef.current?.id)
            return;

        setUserQCount(0);

        setSongQueue(newQueue);
        setSongQueueIndex(startPosition); // TODO Reduce these two sets?
        _playSong(newQueue[startPosition]);
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

    const removeFromQueue = (queueIndex: number) => {
        const newQueue = [...songQueue];

        newQueue.splice(queueIndex, 1);
        setSongQueue(newQueue);

        //If we remove something from the queue that's behind the current playing song
        //the order will get messed up without this
        if (queueIndex < songQueueIndex) {
            setSongQueueIndex(songQueueIndex - 1);
        }
    };

    const durationChange = (elapsed) => {
        setSongPosition(~~elapsed);
    };

    const seekSongTo = (newPosition: number) => {
        audioRef.audioEl.current.currentTime = newPosition;
        setSongPosition(newPosition);
    };

    //TODO: Investigate if this is reasonable, passing the ref itself is kind of annoying.
    // But if we can avoid using a ref, or perhaps better understand the performance issues this may cause.
    const currentPlayingSong = currentPlayingSongRef.current;
    return (
        <MusicContext.Provider //TODO: Should this provider be split into multiple providers?
            value={{
                playerStatus,
                currentPlayingSong,
                songQueueIndex,
                songQueue,
                songPosition, //TODO: Performance concern?
                volume,
                playPause,
                playPrevious,
                playNext,
                toggleFlag,
                startPlayingWithNewQueue,
                addToQueue,
                removeFromQueue,
                seekSongTo,
                setVolume
            }}
        >
            <ReactAudioPlayer
                ref={(element) => {
                    if (element == undefined) return;
                    audioRef = element;
                }}
                volume={volume / 100}
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
