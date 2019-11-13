import React, { useEffect, useState } from 'react';
import { PLAYERSTATUS } from './enum/PlayerStatus';
import { Howl, Howler } from 'howler';
import { useHotkeys } from 'react-hotkeys-hook';
import { AuthKey } from './logic/Auth';

interface MusicContextProps {
    authKey: AuthKey;
}

export const MusicContext = React.createContext({
    playerStatus: PLAYERSTATUS.STOPPED,
    playingSongID: -1,
    playingSongArt: '',
    playPause: () => {},
    startPlaying: (url: string, songID: number, songArt: string) => {}
});

export const MusicContextProvider: React.FC<MusicContextProps> = (props) => {
    const [playerStatus, setPlayerStatus] = useState(PLAYERSTATUS.STOPPED);
    const [playingSongID, setPlayingSongID] = useState(-1);
    const [playingSongArt, setPlayingSongArt] = useState('');

    const howl = React.useRef<Howl>(null);

    useHotkeys('space', () => playPause(), [playerStatus]);

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

    const startPlaying = (playURL: string, songID: number, songArt: string) => {
        if (songID === playingSongID) return;
        if (
            playerStatus === PLAYERSTATUS.PLAYING ||
            playerStatus === PLAYERSTATUS.PAUSED
        ) {
            howl.current.stop();
        }
        setPlayingSongID(songID);
        setPlayingSongArt(songArt);
        howl.current = new Howl({
            src: [playURL],
            format: 'mp3', //Howler is broken, this bypasses https://github.com/goldfire/howler.js/issues/1248
            onload: () => {
                console.log('LOADED');
                howl.current.play();
                setPlayerStatus(PLAYERSTATUS.PLAYING);
            },
            onloaderror: (id, err) => {
                console.log('ERROR', err);
                Howler.unload();
            }
        });
    };

    return (
        <MusicContext.Provider
            value={{
                playerStatus,
                playingSongID,
                playingSongArt,
                playPause,
                startPlaying
            }}
        >
            {props.children}
        </MusicContext.Provider>
    );
};
