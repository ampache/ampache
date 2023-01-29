import React, { useRef, useState } from 'react';
import SVG from 'react-inlinesvg';
import { Link, withRouter, RouteComponentProps } from 'react-router-dom';
import logo from '~images/ampache-dark.png';
import { DebounceInput } from 'react-debounce-input';
import { useHotkeys } from 'react-hotkeys-hook';
import hamburgerIcon from '~images/icons/svg/hamburger.svg';
import playlistIcon from '~images/icons/svg/playlist.svg';

import * as style from './index.styl';

interface HeaderProps extends RouteComponentProps<never> {
    username: string;
    toggleQueueBar: () => void;
    toggleSideBar: () => void;
    history: any;
}

const Header = (props: HeaderProps) => {
    const [query, setQuery] = useState('');
    const searchBox = useRef(null);

    useHotkeys('ctrl+f', (e) => {
        e.preventDefault();
        searchBox.current.focus();
    });

    const searchSubmit = (value) => {
        props.history.push(`/search/${value}`);
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
                    src={hamburgerIcon}
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
                    src={playlistIcon}
                    title='Show queue'
                    role='button'
                />
            </div>
        </header>
    );
};

export default withRouter(Header);
