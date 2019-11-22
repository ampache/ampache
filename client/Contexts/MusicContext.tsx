import React, { useState } from 'react';
import { PLAYERSTATUS } from '../enum/PlayerStatus';
import { Howl, Howler } from 'howler';
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
    playSong: (song: Song) => {},
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

    const howl = React.useRef<Howl>(null);

    useHotkeys(
        'space',
        (e) => {
            e.preventDefault();
            playPause();
        },
        [playerStatus]
    );

    const playPause = () => {
        console.log(playerStatus);
        if (playerStatus === PLAYERSTATUS.PLAYING) {
            howl.current.pause();
            setPlayerStatus(PLAYERSTATUS.PAUSED);
        } else if (playerStatus === PLAYERSTATUS.PAUSED) {
            howl.current.play();
            setPlayerStatus(PLAYERSTATUS.PLAYING);
        }
    };

    const playPrevious = () => {
        const previousSong = songQueue[songQueueIndex - 1];
        setSongQueueIndex(songQueueIndex - 1);
        setCurrentPlayingSong(previousSong);

        playSong(previousSong);
    };

    const playNext = () => {
        const nextSong = songQueue[songQueueIndex + 1];
        setSongQueueIndex(songQueueIndex + 1);
        setCurrentPlayingSong(nextSong);
        if (userQCount > 0) setUserQCount(userQCount - 1);
        playSong(nextSong);
    };

    const playSong = (song: Song) => {
        if (
            playerStatus === PLAYERSTATUS.PLAYING ||
            playerStatus === PLAYERSTATUS.PAUSED
        ) {
            howl.current.stop();
        }
        howl.current = new Howl({
            src: [song.url],
            format: 'mp3', //Howler is broken, this bypasses https://github.com/goldfire/howler.js/issues/1248
            onload: () => {
                howl.current.play();
                setPlayerStatus(PLAYERSTATUS.PLAYING);
            },
            onloaderror: (id, err) => {
                console.log('ERROR', err);
                Howler.unload();
            },
            onend: () => {
                if (songQueueIndex === songQueue.length) {
                    setCurrentPlayingSong({} as Song);
                    setPlayerStatus(PLAYERSTATUS.STOPPED);

                    return;
                }
                playNext();
            }
        });
    };

    const startPlayingWithNewQueue = (song: Song, newQueue: Song[]) => {
        if (song.id === currentPlayingSong?.id) return;

        const queueIndex = newQueue.findIndex((o) => o.id === song.id);
        if (
            playerStatus === PLAYERSTATUS.PLAYING ||
            playerStatus === PLAYERSTATUS.PAUSED
        ) {
            howl.current.stop();
        }
        setCurrentPlayingSong(song);
        setSongQueue(newQueue);
        setSongQueueIndex(queueIndex);
        console.log(newQueue, queueIndex);
        playSong(song);
    };

    const addToQueue = (song: Song, next: Boolean) => {
        let newQueue = [...songQueue];
        console.log('ADD', userQCount);
        if (next) {
            //splice starts at 1, so we don't need +1
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
                playSong,
                startPlayingWithNewQueue,
                addToQueue
            }}
        >
            {props.children}
        </MusicContext.Provider>
    );
};
