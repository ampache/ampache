import { PLAYERSTATUS } from '../../enum/PlayerStatus';
import React, { useContext } from 'react';
import { MusicContext } from '../../Contexts/MusicContext';

const MusicPlayer: React.FC = (props) => {
    const musicContext = useContext(MusicContext);

    return (
        <div className='player'>
            <img
                className='songArt'
                src={musicContext.currentPlayingSong?.art}
            />
            <div
                className={
                    musicContext.songQueueIndex == -1
                        ? 'disabled controls'
                        : 'controls'
                }
            >
                <div className='previousSong'>
                    <img
                        src='https://img.icons8.com/flat_round/50/000000/back--v2.png'
                        onClick={() => {
                            musicContext.playPrevious();
                        }}
                        className={
                            musicContext.songQueueIndex == 0 ? 'disabled' : ''
                        }
                    />
                </div>
                <div className='playPause'>
                    {musicContext.playerStatus === PLAYERSTATUS.STOPPED ||
                    musicContext.playerStatus === PLAYERSTATUS.PAUSED ? (
                        <img
                            src='https://img.icons8.com/flat_round/50/000000/play.png'
                            alt='Play'
                            onClick={musicContext.playPause}
                        />
                    ) : (
                        <img
                            src='https://img.icons8.com/flat_round/50/000000/pause--v2.png'
                            alt='Pause'
                            onClick={musicContext.playPause}
                        />
                    )}
                </div>
                <div className='nextSong'>
                    <img
                        src='https://img.icons8.com/flat_round/50/000000/circled-chevron-right.png'
                        onClick={() => {
                            musicContext.playNext();
                        }}
                        className={
                            musicContext.songQueueIndex ==
                            musicContext.songQueue.length - 1
                                ? 'disabled'
                                : ''
                        }
                    />
                </div>
            </div>
        </div>
    );
};

export default MusicPlayer;
