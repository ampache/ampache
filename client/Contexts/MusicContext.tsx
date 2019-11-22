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
    currentPlayingSongID: -1,
    songQueueIndex: -1,
    songQueue: [],
    playingSongArt: '',
    playPause: () => {},
    playPrevious: () => {},
    playNext: () => {},
    startPlaying: (song: Song, newQueue: Song[]) => {}
});

export const MusicContextProvider: React.FC<MusicContextProps> = (props) => {
    const [playerStatus, setPlayerStatus] = useState(PLAYERSTATUS.STOPPED);
    const [currentPlayingSongID, setCurrentPlayingSongID] = useState(-1);
    const [playingSongArt, setPlayingSongArt] = useState('');
    const [songQueue, setSongQueue]: [
        Song[],
        React.Dispatch<React.SetStateAction<any[]>>
    ] = useState([]);
    const [songQueueIndex, setSongQueueIndex] = useState(-1);

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
        startPlaying(songQueue[songQueueIndex - 1], songQueue);
    };
    const playNext = () => {
        startPlaying(songQueue[songQueueIndex + 1], songQueue);
    };

    const startPlaying = (song: Song, newQueue: Song[]) => {
        if (song.id === currentPlayingSongID) return;

        const queueIndex = newQueue.findIndex((o) => o.id === song.id);
        if (
            playerStatus === PLAYERSTATUS.PLAYING ||
            playerStatus === PLAYERSTATUS.PAUSED
        ) {
            howl.current.stop();
        }
        setCurrentPlayingSongID(song.id);
        setPlayingSongArt(song.art);
        setSongQueue(newQueue);
        setSongQueueIndex(queueIndex);
        console.log(newQueue, queueIndex);

        howl.current = new Howl({
            src: [song.url],
            format: 'mp3', //Howler is broken, this bypasses https://github.com/goldfire/howler.js/issues/1248
            onload: () => {
                console.log('LOADED');
                howl.current.play();
                setPlayerStatus(PLAYERSTATUS.PLAYING);
            },
            onloaderror: (id, err) => {
                console.log('ERROR', err);
                Howler.unload();
            },
            onend: () => {
                const newSong = newQueue[queueIndex + 1];
                if (newSong == undefined) {
                    setCurrentPlayingSongID(-1);
                    setPlayerStatus(PLAYERSTATUS.STOPPED);
                    setPlayingSongArt('');

                    return;
                }
                startPlaying(newSong, newQueue);
            }
        });
    };

    return (
        <MusicContext.Provider
            value={{
                playerStatus,
                currentPlayingSongID,
                songQueueIndex,
                songQueue,
                playingSongArt,
                playPause,
                playPrevious,
                playNext,
                startPlaying
            }}
        >
            {props.children}
        </MusicContext.Provider>
    );
};
