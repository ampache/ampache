import React, { useEffect, useState } from 'react';
import { PLAYERSTATUS } from '../enum/PlayerStatus';
import { useHotkeys } from 'react-hotkeys-hook';
import { AuthKey } from '../logic/Auth';
import { Song } from '../logic/Song';

interface MusicContextProps {
    authKey: AuthKey;
}

export const MusicContext = React.createContext({
    playerStatus: PLAYERSTATUS.STOPPED,
    currentPlayingSong: {} as Song,
    songQueueIndex: -1,
    songQueue: [] as Song[],
    playPause: () => {},
    playPrevious: () => {},
    playNext: () => {},
    startPlayingWithNewQueue: (song: Song, newQueue: Song[]) => {},
    addToQueue: (song: Song, next: Boolean) => {}
});

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
    const [songQueueIndex, setSongQueueIndex] = useState(-1);
    const [userQCount, setUserQCount] = useState(0);

    const audioRef: React.MutableRefObject<HTMLAudioElement> = React.useRef(
        null
    );

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
    }, [currentPlayingSong]);

    useHotkeys(
        'space',
        (e) => {
            e.preventDefault();
            playPause();
        },
        [playerStatus]
    );

    const playPause = () => {
        const isPaused = audioRef.current?.paused;
        if (isPaused) {
            audioRef.current.play();
            return;
        } else {
            audioRef.current.pause();
        }
    };

    const playPrevious = () => {
        const previousSong = songQueue[songQueueIndex - 1];
        setSongQueueIndex(songQueueIndex - 1);
        _playSong(previousSong);
    };

    const playNext = () => {
        console.log(songQueueIndex);
        const nextSong = songQueue[songQueueIndex + 1];
        setSongQueueIndex(songQueueIndex + 1);
        if (userQCount > 0) setUserQCount(userQCount - 1);
        _playSong(nextSong);
    };

    const _playSong = async (song: Song) => {
        if (!song) {
            return console.error(
                'Playing an undefined song, something is wrong.'
            );
        }
        audioRef.current.src = song.url;
        audioRef.current.title = `${song.artist.name} - ${song.title}`;
        audioRef.current.play();
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
    };

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

    const addToQueue = (song: Song, next: Boolean) => {
        let newQueue = [...songQueue];
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

    return (
        <MusicContext.Provider
            value={{
                playerStatus,
                currentPlayingSong,
                songQueueIndex,
                songQueue,
                playPause,
                playPrevious,
                playNext,
                startPlayingWithNewQueue,
                addToQueue
            }}
        >
            <audio
                ref={audioRef}
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
