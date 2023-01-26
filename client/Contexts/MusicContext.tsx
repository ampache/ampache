import React, { useCallback, useEffect, useState } from 'react';
import { PLAYERSTATUS } from '~enum/PlayerStatus';
import { useHotkeys } from 'react-hotkeys-hook';
import { Song, useGetSong } from '~logic/Song';
import ReactAudioPlayer from 'react-audio-player';
import { useMusicStore } from '~store';
import Cookies from 'js-cookie';
import { useQueryClient } from 'react-query';
import shallow from '~node_modules/zustand/shallow';

export interface MusicContextInterface {
    volume: number;
    playPause: () => void;
    playPrevious: () => void;
    playNext: () => void;
    startPlayingWithNewQueue: (newQueue: string[], startPosition?) => void;
    changeQueuePosition: (newPosition: number) => void;
    seekSongTo: (newPosition: number) => void;
    setVolume: (newVolume: number) => void;
}

export const MusicContext = React.createContext({} as MusicContextInterface);

const cookieVolume = parseInt(Cookies.get('volume') ?? 100);

export const MusicContextProvider: React.FC = (props) => {
    const [volume, setVolume] = useState(cookieVolume);
    const queryClient = useQueryClient();

    let audioRef = undefined; //TODO: Should this be useRef?
    const {
        songQueueIndex,
        songQueue,
        userQCount,
        playerStatus,
        setSongQueueIndex,
        setPlayerStatus,
        setUserQCount,
        setSongQueue,
        setSongPosition
    } = useMusicStore(
        (state) => ({
            songQueue: state.songQueue,
            songQueueIndex: state.songQueueIndex,
            userQCount: state.userQCount,
            playerStatus: state.playerStatus,
            setSongQueueIndex: state.setSongQueueIndex,
            setPlayerStatus: state.setPlayerStatus,
            setUserQCount: state.setUserQCount,
            setSongQueue: state.setSongQueue,
            setSongPosition: state.setSongPosition
        }),
        shallow
    );
    const currentPlayingSongId = songQueue[songQueueIndex];

    const { refetch: fetchSong } = useGetSong(currentPlayingSongId, {
        enabled: false
    });

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
        if (!currentPlayingSongId) return;

        const isPaused = audioRef.audioEl?.current.paused;
        if (isPaused) {
            audioRef.audioEl.current.play();
            return;
        } else {
            audioRef.audioEl.current.pause();
        }
    }, [audioRef, currentPlayingSongId]);

    const _playSong = useCallback(
        async (songID: string) => {
            if (!songID) {
                return console.error(
                    'Playing an undefined song, something is wrong.'
                );
            }
            if (typeof songID !== 'string') {
                throw new Error('_playSong received a non string');
            }
            const song = queryClient.getQueryData<Song>(['song', songID]);
            if (!song) {
                console.warn(`Failed to get song(${songID}) from cache...`);
                return fetchSong().then(() => {
                    _playSong(songID);
                });
            }

            // useStore.getState().startPlayingSong(song);

            // eslint-disable-next-line immutable/no-mutation
            audioRef.audioEl.current.src = song.url;
            // eslint-disable-next-line immutable/no-mutation
            audioRef.audioEl.current.title = `${song.artist.name} - ${song.title}`;
            audioRef.audioEl.current.play();

            if ('mediaSession' in navigator) {
                // eslint-disable-next-line immutable/no-mutation
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
        [audioRef?.audioEl, fetchSong, playPause, queryClient]
    );

    const playPrevious = useCallback(() => {
        setSongQueueIndex(songQueueIndex - 1);
        _playSong(songQueue[songQueueIndex - 1]);
    }, [_playSong, songQueue, songQueueIndex]);

    const playNext = useCallback(() => {
        setSongQueueIndex(songQueueIndex + 1);
        if (userQCount > 0) setSongQueueIndex(userQCount - 1);
        _playSong(songQueue[songQueueIndex + 1]);
    }, [_playSong, songQueue, songQueueIndex, userQCount]);

    //This could use a better name perhaps, as it just exists to update the state in MusicContext
    //To reflect that the song is flagged, so anything that relies on MusicContext is also up-to-date
    // const flagCurrentSong = (favorite: boolean) => {
    //     const newQ = songQueue.map((songId) => {
    //         if (songId === currentPlayingSong?.id) {
    //             return { ...song, flag: favorite };
    //         }
    //         return song;
    //     });
    //
    //     setSongQueue(newQ);
    // };

    useEffect(() => {
        if (!currentPlayingSongId) return;
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
    }, [currentPlayingSongId, playNext, playPrevious]);

    const songIsOver = () => {
        if (songQueueIndex === songQueue.length - 1) {
            setPlayerStatus(PLAYERSTATUS.STOPPED);
            return;
        }
        playNext();
    };

    const startPlayingWithNewQueue = useCallback(
        (newQueue: string[], startPosition = 0) => {
            if (newQueue[startPosition] === currentPlayingSongId) return;

            setUserQCount(0);

            const newQueueList = [...newQueue];

            setSongQueue(newQueueList);
            setSongQueueIndex(startPosition); // TODO Reduce these two sets?

            _playSong(newQueueList[startPosition]);
        },
        [
            _playSong,
            currentPlayingSongId,
            setSongQueue,
            setSongQueueIndex,
            setUserQCount
        ]
    );

    const changeQueuePosition = useCallback(
        (newPosition: number) => {
            setUserQCount(0);
            setSongQueueIndex(newPosition);
            _playSong(songQueue[newPosition]);
        },
        [_playSong, setSongQueueIndex, setUserQCount, songQueue]
    );

    const durationChange = (elapsed) => {
        setSongPosition(~~elapsed);
    };

    const seekSongTo = useCallback(
        (newPosition: number) => {
            // eslint-disable-next-line immutable/no-mutation
            audioRef.audioEl.current.currentTime = newPosition;
            setSongPosition(newPosition);
        },
        [audioRef, setSongPosition]
    );

    return (
        <MusicContext.Provider //TODO: Should this provider be split into multiple providers?
            value={{
                volume,
                playPause,
                playPrevious,
                playNext,
                startPlayingWithNewQueue,
                changeQueuePosition,
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
