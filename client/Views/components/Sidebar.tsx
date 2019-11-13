import React, { useContext } from 'react';
import { Link } from 'react-router-dom';
import { MusicContext } from '../../MusicContext';
import { PLAYERSTATUS } from '../../enum/PlayerStatus';

const Sidebar: React.FC = () => {
    const musicContext = useContext(MusicContext);

    return (
        <div className='sidebar'>
            <section>
                <h4>Browse Music</h4>
                <ul>
                    <li>
                        <Link to='/artists'>Artists</Link>
                    </li>
                    <li>
                        <Link to='/playlists'>Playlists</Link>
                    </li>
                    <li>
                        <Link to='/radio'>Radio Stations</Link>
                    </li>
                </ul>
            </section>
            <section>
                <h4>Browse Movie</h4>
                <ul>
                    <li>
                        <Link to='/musicclips'>Music Clips</Link>
                    </li>
                    <li>
                        <Link to='/tvshows'>TV Shows</Link>
                    </li>
                    <li>
                        <Link to='/movies'>Movies</Link>
                    </li>
                    <li>
                        <Link to='/personalvideos'>Personal Videos</Link>
                    </li>
                </ul>
            </section>
            <section>
                <h4>Filters</h4>
                <form>
                    <label>Starts With</label>
                    <input type='text' />
                    <label>Catalog</label>
                    <select />
                </form>
            </section>
            <div className='player'>
                <img className='songArt' src={musicContext.playingSongArt} />
                <div className='controls'>
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
            </div>
        </div>
    );
};

export default Sidebar;
