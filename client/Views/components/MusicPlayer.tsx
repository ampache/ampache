import React, { useContext } from 'react';
import { MusicContext } from '../../Contexts/MusicContext';
import CDImage from '/images/icons/svg/CD.svg';

const MusicPlayer: React.FC = () => {
    const musicContext = useContext(MusicContext);

    return (
        <div className='songArt'>
            <img
                src={musicContext.currentPlayingSong?.art || CDImage}
                alt='cover art'
            />
        </div>
    );
};

export default MusicPlayer;
