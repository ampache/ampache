import React, { useContext } from 'react';
import { PLAYERSTATUS } from '../../enum/PlayerStatus';
import { MusicContext } from '../../Contexts/MusicContext';

const MusicControl: React.FC = () => {
    const musicContext = useContext(MusicContext);

    return (
        <div className='musicControl'>
            <div className='controls'>
                <div className='previousSong'>
                    <img
                        src='https://img.icons8.com/flat_round/50/000000/back--v2.png'
                        alt='Back'
                        onClick={() => {
                            musicContext.playPrevious();
                        }}
                        className={
                            musicContext.songQueueIndex <= 0 ? 'disabled' : ''
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
                        alt='Next'
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
            <div className='seekBar'>
                <div
                    className='filled'
                    style={{ width: musicContext.songPosition + '%' }}
                />
            </div>
        </div>
    );
};

export default MusicControl;
