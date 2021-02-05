import React from 'react';
import { BrowserRouter, Redirect, Route, Switch } from 'react-router-dom';
import Cookies from 'js-cookie';

import App from './Views/App';
import Home from './Views/Home/';
import Account from './Views/Account/';
import SearchView from './Views/Search/';
import LoginView from './Views/Login/';
import NotFound from './Views/404/';
import handshake, { AuthKey } from './logic/Auth';
import { getUser, User } from '~logic/User';
import AlbumView from './Views/Album';
import ArtistView from './Views/Artist';
import { MusicContextProvider } from '~Contexts/MusicContext';
import ArtistsView from './Views/Artists';
import AlbumsView from './Views/Albums';
import AmpacheError from './logic/AmpacheError';
import PlaylistsView from './Views/Playlists';
import PlaylistView from './Views/Playlist';
import ReactLoading from 'react-loading';

interface RouterState {
    authKey: AuthKey;
    username: string;
    user: User;
    loading: boolean;
}

export default class Root extends React.PureComponent<void, RouterState> {
    private readonly handleLogin: (
        username: string,
        password: string
    ) => Promise<void | AmpacheError | Error>;
    constructor(props) {
        super(props);

        this.state = {
            authKey: Cookies.get('authKey'),
            username: Cookies.get('username'),
            user: null,
            loading: true
        };

        this.handleLogin = (username: string, password: string) => {
            return handshake(username, password)
                .then((newAuthKey: AuthKey) => {
                    this.setState({ authKey: newAuthKey });
                    Cookies.set('authKey', newAuthKey);
                    Cookies.set('username', username);
                    return getUser(username, newAuthKey)
                        .then((user: User) => {
                            user.authKey = newAuthKey;
                            this.setState({ user });
                        })
                        .catch((error: AmpacheError) => {
                            console.error('HANDSHAKE-GETUSERFAILED', error); //TODO: Error handling
                            throw error;
                        });
                })
                .catch((error: AmpacheError) => {
                    throw error;
                });
        };
        this.handleLogout = this.handleLogout.bind(this);
    }

    componentDidMount() {
        if (this.state.authKey != null) {
            getUser(this.state.username, this.state.authKey)
                .then((user: User) => {
                    user.authKey = this.state.authKey;
                    this.setState({ user, loading: false });
                })
                .catch((error) => {
                    console.error('GETUSERFAILED', error); //TODO: Error handling
                    this.handleLogout();
                });
        } else {
            this.setState({ loading: false });
        }
    }

    private handleLogout() {
        Cookies.remove('authKey');
        Cookies.remove('username');
        this.setState({ authKey: null, username: null, user: null });
    }

    render() {
        console.log(this.state);
        if (this.state.loading) {
            return <ReactLoading />;
        }

        if (this.state.authKey == null || this.state.user == null) {
            return (
                <BrowserRouter basename='/newclient'>
                    <Route
                        render={(props) => (
                            <LoginView
                                {...props}
                                handleLogin={this.handleLogin}
                            />
                        )}
                    />
                </BrowserRouter>
            );
        }
        return (
            <BrowserRouter basename='/newclient'>
                <Switch>
                    {/*TODO: Do i need this switch?*/}
                    <Route exact path='/login'>
                        <Redirect to='/' />
                    </Route>
                    <Route
                        exact
                        path='/logout'
                        render={() => {
                            this.handleLogout();
                            return <Redirect to='/login' />;
                        }}
                    />
                    <MusicContextProvider authKey={this.state.authKey}>
                        <App user={this.state.user}>
                            <Switch>
                                <Route
                                    exact
                                    path='/'
                                    render={(props) => (
                                        <Home
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/account'
                                    render={(props) => (
                                        <Account
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/album/:albumID'
                                    render={(props) => (
                                        <AlbumView
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/albums'
                                    render={(props) => (
                                        <AlbumsView
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/artist/:artistID'
                                    render={(props) => (
                                        <ArtistView
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/artists'
                                    render={(props) => (
                                        <ArtistsView
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/playlists'
                                    render={(props) => (
                                        <PlaylistsView
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/playlist/:playlistID'
                                    render={(props) => (
                                        <PlaylistView
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/search/:searchQuery?'
                                    render={(props) => (
                                        <SearchView
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    path='*'
                                    render={(props) => <NotFound {...props} />}
                                />
                            </Switch>
                        </App>
                    </MusicContextProvider>
                </Switch>
            </BrowserRouter>
        );
    }
}
