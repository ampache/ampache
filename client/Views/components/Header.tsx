import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import logo from '/images/ampache-dark.png';
import userIcon from '/images/icons/svg/user.svg';
import { withRouter } from 'react-router-dom';

interface HeaderProps {
    history: any;
    username: string;
}

//TODO: Figure out how to use HeaderProps type here
const Header = withRouter(({ history, ...props }: any) => {
    const [query, setQuery] = useState('');

    const searchSubmit = (e) => {
        e.preventDefault();
        history.push(`/search/${query}`);
    };

    return (
        <header>
            <div className='logoContainer'>
                <Link className='logo' to='/'>
                    <img src={logo} alt='Ampache Logo' />
                </Link>
            </div>
            <div className='search'>
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
            <div className='account'>
                <img src={userIcon} alt='user icon' />
                <div className='options'>
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
