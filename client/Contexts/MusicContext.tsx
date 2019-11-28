import React, { useState } from 'react';
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

    const audioRef = React.useRef(null);

    useHotkeys(
        'space',
        (e) => {
            e.preventDefault();
            playPause();
        },
        [playerStatus]
    );

    const playPause = () => {
        console.log(playerStatus, audioRef.current);
        if (playerStatus === PLAYERSTATUS.PLAYING) {
            audioRef.current.pause();
            setPlayerStatus(PLAYERSTATUS.PAUSED);
        } else if (playerStatus === PLAYERSTATUS.PAUSED) {
            audioRef.current.play();
            setPlayerStatus(PLAYERSTATUS.PLAYING);
        }
    };

    const playPrevious = () => {
        const previousSong = songQueue[songQueueIndex - 1];
        setSongQueueIndex(songQueueIndex - 1);

        _playSong(previousSong);
    };

    const playNext = () => {
        const nextSong = songQueue[songQueueIndex + 1];
        setSongQueueIndex(songQueueIndex + 1);
        if (userQCount > 0) setUserQCount(userQCount - 1);
        _playSong(nextSong);
    };

    const _playSong = async (song: Song) => {
        setCurrentPlayingSong(song);
        setPlayerStatus(PLAYERSTATUS.PLAYING);
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

        _playSong(song);
        setSongQueue(newQueue);
        setSongQueueIndex(queueIndex);
        console.log(newQueue, queueIndex);
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
                onEnded={() => songIsOver()}
                src={currentPlayingSong?.url}
                autoPlay
            />
            {props.children}
        </MusicContext.Provider>
    );
};
