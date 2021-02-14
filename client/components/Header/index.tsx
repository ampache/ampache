import React, { FormEvent, useRef, useState } from 'react';
import SVG from 'react-inlinesvg';
import { Link, withRouter } from 'react-router-dom';
import logo from '~images/ampache-dark.png';

import style from './index.styl';
import { DebounceInput } from 'react-debounce-input';
import { useHotkeys } from 'react-hotkeys-hook';
import { User } from '~logic/User';

interface HeaderProps {
    username: string;
    toggleQueueBar: () => void;
    toggleSideBar: () => void;
    match: {
        params: {
            searchQuery: string;
        };
    };
}

//TODO: Figure out how to use HeaderProps type here
const Header = withRouter(({ history, ...props }: any) => {
    const [query, setQuery] = useState('');
    const searchBox = useRef(null);

    useHotkeys('ctrl+f', (e) => {
        e.preventDefault();
        searchBox.current.focus();
    });

    const searchSubmit = (value) => {
        history.push(`/search/${value}`);
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
                    className='icon icon-button'
                    src={require('~images/icons/svg/hamburger.svg')}
                    title='Show menu'
                    role='button'
                />
            </div>
            <div className={style.search}>
                <form onSubmit={(e) => e.preventDefault()}>
                    <DebounceInput
                        type='text'
                        placeholder='Search'
                        autoComplete='off'
                        value={query}
                        debounceTimeout={300}
                        onChange={(event) => {
                            event.preventDefault();
                            setQuery(event.target.value);
                            searchSubmit(event.target.value);
                        }}
                        onSubmit={(event) => {
                            console.log('SUBMIT');
                            searchSubmit(event.target.value);
                        }}
                        inputRef={searchBox}
                    />
                </form>
            </div>
            <div className={style.queueIcon} onClick={props.toggleQueueBar}>
                <SVG
                    className='icon icon-button'
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
