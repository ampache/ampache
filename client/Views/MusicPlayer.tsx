import React, { useContext } from 'react';
import { MusicContext } from '../MusicContext';
import { PLAYERSTATUS } from '../enum/PlayerStatus';

const MusicPlayer = () => {
    const musicContext = useContext(MusicContext);
    return (
        <>
            <div className='musicPlayer'>
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
        </>
    );
};
export default MusicPlayer;
