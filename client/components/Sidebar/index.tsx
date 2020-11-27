import React from 'react';
import { Link } from 'react-router-dom';

import style from './index.module.styl';

const Sidebar: React.FC = () => {
    return (
        <div className={style.sidebar}>
            <section>
                <h4>Library</h4>
                <ul>
                    <li>
                        <Link to='/artists'>Artists</Link>
                    </li>
                    <li>
                        <Link to='/albums'>Albums</Link>
                    </li>
                    <li>
                        <Link to='/songs'>Songs</Link>
                    </li>
                    <li>
                        <Link to='/playlists'>Playlists</Link>
                    </li>
                    <li>
                        <Link to='/smartlists'>Smartlists</Link>
                    </li>
                    <li>
                        <Link to='/favorites'>Favorites</Link>
                    </li>
                    <li>
                        <Link to='/random'>Random</Link>
                    </li>
                </ul>
            </section>
            <section>
                <h4>Control</h4>
                <ul>
                    <li>
                        <Link to='/democratic'>Democratic</Link>
                    </li>
                    <li>
                        <Link to='/radio'>Radio Stations</Link>
                    </li>
                    <li>
                        <Link to='/localplay'>Localplay</Link>
                    </li>
                </ul>
            </section>
            <section>
                <h4>Insights</h4>
                <ul>
                    <li>
                        <Link to='/recent'>Recent</Link>
                    </li>
                    <li>
                        <Link to='/newest'>Newest</Link>
                    </li>
                    <li>
                        <Link to='/popular'>Popular</Link>
                    </li>
                    <li>
                        <Link to='/top'>Top Rated</Link>
                    </li>
                    <li>
                        <Link to='/tagcloud'>Tag Cloud</Link>
                    </li>
                    <li>
                        <Link to='/statistics'>Statistics</Link>
                    </li>
                </ul>
            </section>
        </div>
    );
};

export default Sidebar;
