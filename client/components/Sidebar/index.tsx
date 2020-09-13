import React from 'react';
import { Link } from 'react-router-dom';

import style from './index.module.styl';

const Index: React.FC = () => {
    return (
        <div className={style.sidebar}>
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
        </div>
    );
};

export default Index;
