import React from 'react';
import { Link } from 'react-router-dom';
import '/stylus/main.styl';
import logo from '/images/ampache-dark.png';
import userIcon from '/images/icons/svg/user.svg';
import Sidebar from './components/Sidebar';
import { User } from '../logic/User';

interface AppViewProps {
    user: User;
}

export default class AppView extends React.PureComponent<AppViewProps> {
    render() {
        if (this.props.user == null) {
            return <span>Loading...</span>;
        }
        console.log(this.props);

        return (
            <>
                <header>
                    <div className='logoContainer'>
                        <Link className='logo' to='/'>
                            <img src={logo} alt='Ampache Logo' />
                        </Link>
                    </div>
                    <div className='search'>
                        <form>
                            <input
                                type='text'
                                placeholder='Search'
                                name='rule_1_input'
                                autoComplete='off'
                            />
                        </form>
                    </div>
                    <div className='account'>
                        <img src={userIcon} alt='user icon' />
                        <div className='options'>
                            <ul>
                                <li>
                                    <Link to='/account'>
                                        {this.props.user.username}
                                    </Link>
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
                <div className='container'>
                    <Sidebar />
                    <div className='content'>{this.props.children}</div>
                </div>
            </>
        );
    }
}
