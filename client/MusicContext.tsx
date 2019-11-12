import React, { useState } from 'react';
import { PLAYERSTATUS } from './enum/PlayerStatus';
import { Howl, Howler } from 'howler';

interface MusicPlayerContextState {
    playerStatus: PLAYERSTATUS;
}

export const MusicContext = React.createContext({
    playerStatus: PLAYERSTATUS.STOPPED,
    playingSongID: -1,
    playPause: () => {},
    startPlaying: (url: string, songID: number) => {}
});
export const MusicContextProvider = (props) => {
    const [playerStatus, setPlayerStatus] = useState(PLAYERSTATUS.STOPPED);
    const [playingSongID, setPlayingSongID] = useState(-1);

    const howl = React.useRef<Howl>(null);

    const playPause = () => {
        console.log('PLAYPAUSE', howl);
        if (playerStatus === PLAYERSTATUS.PLAYING) {
            howl.current.pause();
            setPlayerStatus(PLAYERSTATUS.PAUSED);
        } else if (playerStatus === PLAYERSTATUS.PAUSED) {
            howl.current.play();
            setPlayerStatus(PLAYERSTATUS.PLAYING);
        }
    };

    const startPlaying = (playURL: string, songID: number) => {
        if (songID === playingSongID) return;
        if (
            playerStatus === PLAYERSTATUS.PLAYING ||
            playerStatus === PLAYERSTATUS.PAUSED
        ) {
            howl.current.stop();
        }
        setPlayingSongID(songID);
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
                playPause,
                startPlaying
            }}
        >
            {props.children}
        </MusicContext.Provider>
    );
};
