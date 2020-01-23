import React, { useContext } from 'react';
import { MusicContext } from '../../Contexts/MusicContext';
import CDImage from '/images/icons/svg/CD.svg';
import { Link } from 'react-router-dom';

const CurrentPlaying: React.FC = () => {
    const musicContext = useContext(MusicContext);

    return (
        <div className='currentPlaying'>
            {musicContext.currentPlayingSong == undefined && (
                <img
                    src={CDImage}
                    alt='default cover art'
                    className='albumArt'
                />
            )}
            {musicContext.currentPlayingSong != undefined && (
                <Link
                    className='albumArt'
                    to={`/album/${musicContext.currentPlayingSong.album.id}`}
                >
                    <img
                        src={musicContext.currentPlayingSong.art}
                        alt='cover art'
                    />
                </Link>
            )}
            <div className='info'>
                <div className='songName'>
                    {musicContext.currentPlayingSong?.title}
                </div>
                <Link
                    className='artistName'
                    to={`/artist/${musicContext.currentPlayingSong?.artist.id}`}
                >
                    {musicContext.currentPlayingSong?.artist.name}
                </Link>
            </div>
        </div>
    );
};

export default CurrentPlaying;
