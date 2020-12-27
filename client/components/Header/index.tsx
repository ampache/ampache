import React, { useState } from 'react';
import SVG from 'react-inlinesvg';
import { Link, withRouter } from 'react-router-dom';
import logo from '~images/ampache-dark.png';

import style from './index.styl';

interface HeaderProps {
    username: string;
    toggleQueueBar: () => void;
    toggleSideBar: () => void;
}

//TODO: Figure out how to use HeaderProps type here
const Header = withRouter(({ history, ...props }: any) => {
    const [query, setQuery] = useState('');

    const searchSubmit = (e) => {
        e.preventDefault();
        history.push(`/search/${query}`);
    };

    return (
        <header className={style.header}>
            <div className={style.logoContainer}>
                <Link className={style.logo} to='/'>
                    <img src={logo} alt='Ampache Logo' />
                </Link>
            </div>
            <div className={style.menuIcon} onClick={props.toggleSideBar}>
                <SVG
                    className='icon-button'
                    src={require('~images/icons/svg/hamburger.svg')}
                    title='Show menu'
                    role='button'
                />
            </div>
            <div className={style.search}>
                <form onSubmit={(e) => searchSubmit(e)}>
                    <input
                        type='text'
                        placeholder='Search'
                        autoComplete='off'
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                    />
                </form>
            </div>
            <div className={style.queueIcon} onClick={props.toggleQueueBar}>
                <SVG
                    className='icon-button'
                    src={require('~images/icons/svg/playlist.svg')}
                    title='Show queue'
                    role='button'
                />
            </div>
            <div className={style.account}>
                <SVG
                    src={require('~images/icons/svg/user.svg')}
                    title='User'
                    role='button'
                />
                <div className={style.options}>
                    <ul>
                        <li>
                            <Link to='/account'>{props.username}</Link>
                        </li>
                        <hr />
                        <li>
                            <Link to='/favorites'>My Favorites</Link>
                        </li>
                        <hr />
                        <li>My Settings</li>
                        <li>
                            <Link to='/interface'>Interface</Link>
                        </li>
                        <li>
                            <Link to='/options'>Options</Link>
                        </li>
                        <li>
                            <Link to='/playlist'>Playlist</Link>
                        </li>
                        <li>
                            <Link to='/plugins'>Plugins</Link>
                        </li>
                        <li>
                            <Link to='/streaming'>Streaming</Link>
                        </li>
                        <hr />
                        <li>
                            <Link to='/logout'>Logout</Link>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
    );
});

export default Header;
