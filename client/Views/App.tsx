import React, {Component} from 'react';
import { Link } from 'react-router-dom';
import '/stylus/main.styl'
import logo from '/images/ampache-dark.png'
import userIcon from '/images/icons/svg/user.svg';

export default class AppView extends Component<any, any> {
    constructor(props) {
        super(props);
        this.state = {
        }
    }

    render() {
        if(this.props.user == null){
            return (<span>Loading...</span>)
        }
        console.log(this.props)

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
                            <input type='text' placeholder='Search' name='rule_1_input' autoComplete='off'/>
                        </form>
                    </div>
                    <div className='account'>
                        <img src={userIcon} alt='user icon'/>
                        <div className='options'>
                            <ul>
                                <li><Link to='/account'>{this.props.user.username}</Link></li>
                                <hr />
                                <li><Link to='/favorites'>My Favorites</Link></li>
                                <hr />
                                <li>My Settings</li>
                                <li><Link to='/interface'>Interface</Link></li>
                                <li><Link to='/options'>Options</Link></li>
                                <li><Link to='/playlist'>Playlist</Link></li>
                                <li><Link to='/plugins'>Plugins</Link></li>
                                <li><Link to='/streaming'>Streaming</Link></li>
                                <hr />
                                <li><Link to='/logout'>Logout</Link></li>
                            </ul>
                        </div>
                    </div>
                </header>
                <div className='container'>
                    <div className='sidebar'>
                        <section>
                            <h4>Browse Music</h4>
                            <ul>
                                <li><Link to='/artists'>Artists</Link></li>
                                <li><Link to='/playlists'>Playlists</Link></li>
                                <li><Link to='/radio'>Radio Stations</Link></li>
                            </ul>
                        </section>
                        <section>
                            <h4>Browse Movie</h4>
                            <ul>
                                <li><Link to='/musicclips'>Music Clips</Link></li>
                                <li><Link to='/tvshows'>TV Shows</Link></li>
                                <li><Link to='/movies'>Movies</Link></li>
                                <li><Link to='/personalvideos'>Personal Videos</Link></li>
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
                    <div className='content'>
                        {this.props.children}
                    </div>
                </div>
            </>
        );
    }
}
