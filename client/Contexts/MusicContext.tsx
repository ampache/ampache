import React, { useCallback, useEffect, useState } from 'react';
import { PLAYERSTATUS } from '~enum/PlayerStatus';
import { useHotkeys } from 'react-hotkeys-hook';
import { Song } from '~logic/Song';
import ReactAudioPlayer from 'react-audio-player';
import { List } from 'immutable';

interface MusicContext {
    playerStatus: PLAYERSTATUS;
    currentPlayingSong: Song;
    songPosition: number;
    volume: number;
    songQueueIndex: number;
    songQueue: List<Song>;
    playPause: () => void;
    playPrevious: () => void;
    playNext: () => void;
    flagCurrentSong: (favorite: boolean) => void;
    startPlayingWithNewQueue: (newQueue: Song[], startPosition?) => void;
    changeQueuePosition: (newPosition: number) => void;
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
    const [songQueue, setSongQueue] = useState<List<Song>>(List());
    const [songPosition, setSongPosition] = useState(0);
    const [songQueueIndex, setSongQueueIndex] = useState(-1);
    const [userQCount, setUserQCount] = useState(0);
    const [volume, setVolume] = useState(100);

    let audioRef = undefined; //TODO: Should this be useRef?

    const currentPlayingSong = songQueue.get(songQueueIndex);

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
        if (!currentPlayingSong) return;

        const isPaused = audioRef.audioEl?.current.paused;
        if (isPaused) {
            audioRef.audioEl.current.play();
            return;
        } else {
            audioRef.audioEl.current.pause();
        }
    }, [audioRef, currentPlayingSong]);

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
        setSongQueueIndex(songQueueIndex - 1);
        _playSong(songQueue.get(songQueueIndex - 1));
    }, [_playSong, songQueue, songQueueIndex]);

    const playNext = useCallback(() => {
        setSongQueueIndex(songQueueIndex + 1);
        if (userQCount > 0) setUserQCount(userQCount - 1);
        _playSong(songQueue.get(songQueueIndex + 1));
    }, [_playSong, songQueue, songQueueIndex, userQCount]);

    //This could use a better name perhaps, as it just exists to update the state in MusicContext
    //To reflect that the song is flagged, so anything that relies on MusicContext is also up-to-date
    const flagCurrentSong = (favorite: boolean) => {
        setSongQueue(songQueue.setIn([songQueueIndex, 'flag'], favorite));
    };

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
        if (songQueueIndex === songQueue.size - 1) {
            setPlayerStatus(PLAYERSTATUS.STOPPED);
            return;
        }
        playNext();
    };

    const startPlayingWithNewQueue = (newQueue: Song[], startPosition = 0) => {
        if (newQueue[startPosition].id === currentPlayingSong?.id) return;

        setUserQCount(0);

        const newQueueList = List(newQueue);

        setSongQueue(newQueueList);
        setSongQueueIndex(startPosition); // TODO Reduce these two sets?

        _playSong(newQueueList.get(startPosition));
    };

    const changeQueuePosition = (newPosition: number) => {
        setUserQCount(0);
        setSongQueueIndex(newPosition);
        _playSong(songQueue.get(newPosition));
    };

    const addToQueue = (song: Song, next: boolean) => {
        console.log('ADD', userQCount);
        if (next) {
            //splice starts at 1, so we don't need +2 //TODO make this comment more clear!
            // newQueue.splice(songQueueIndex + 1 + userQCount, 0, song);
            setUserQCount(userQCount + 1); //TODO: Reducer?

            setSongQueue(
                songQueue.insert(songQueueIndex + 1 + userQCount, song)
            );
            return;
        }

        setSongQueue(songQueue.push(song));
    };

    const removeFromQueue = (queueIndex: number) => {
        setSongQueue(songQueue.delete(queueIndex));

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
                flagCurrentSong,
                startPlayingWithNewQueue,
                changeQueuePosition,
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
