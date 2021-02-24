/* eslint-disable immutable/no-mutation */
import React from 'react';
import { BrowserRouter, Redirect, Route, Switch } from 'react-router-dom';
import Cookies from 'js-cookie';

import AppLayout from './Layouts/App/';
import HomePage from './Pages/Home/';
import AccountPage from './Pages/Account/';
import SearchPage from './Pages/Search/';
import LoginPage from './Pages/Login/';
import NotFound from './Pages/Errors/404/';
import handshake, { AuthKey } from './logic/Auth';
import { getUser, User } from '~logic/User';
import AlbumPage from './Pages/Album';
import AlbumsPage from './Pages/Albums';
import ArtistPage from './Pages/Artist';
import { MusicContextProvider } from '~Contexts/MusicContext';
import ArtistsPage from './Pages/Artists';
import AmpacheError from './logic/AmpacheError';
import PlaylistsPage from './Pages/Playlists';
import PlaylistPage from './Pages/Playlist';
import ReactLoading from 'react-loading';
import GenericError from '~Pages/Errors/GenericError';

interface RouterState {
    authKey: AuthKey;
    username: string;
    user: User;
    loading: boolean;
    error: Error;
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
            loading: true,
            error: null
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

    // componentDidCatch(error: Error, errorInfo) {
    //     console.log('EERERRR');
    //     //TODO: Server log?
    // }

    static getDerivedStateFromError(error) {
        // Update state so the next render will show the fallback UI.
        console.log('DERIVED', error);
        return { error };
    }

    private handleLogout() {
        Cookies.remove('authKey');
        Cookies.remove('username');
        this.setState({ authKey: null, username: null, user: null });
    }

    render() {
        if (this.state.error) {
            return <GenericError message={this.state.error.message} />;
        }
        console.log(this.state);
        if (this.state.loading) {
            return <ReactLoading />;
        }

        if (this.state.authKey == null || this.state.user == null) {
            return (
                <BrowserRouter basename='/newclient'>
                    <Route
                        render={(props) => (
                            <LoginPage
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
                    <MusicContextProvider>
                        <AppLayout user={this.state.user}>
                            <Switch>
                                <Route
                                    exact
                                    path='/'
                                    render={(props) => (
                                        <HomePage
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/account'
                                    render={(props) => (
                                        <AccountPage
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/album/:albumID'
                                    render={(props) => (
                                        <AlbumPage
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/albums'
                                    render={(props) => (
                                        <AlbumsPage
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/artist/:artistID'
                                    render={(props) => (
                                        <ArtistPage
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/artists'
                                    render={(props) => (
                                        <ArtistsPage
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/playlists'
                                    render={(props) => (
                                        <PlaylistsPage
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/playlist/:playlistID'
                                    render={(props) => (
                                        <PlaylistPage
                                            {...props}
                                            user={this.state.user}
                                        />
                                    )}
                                />
                                <Route
                                    exact
                                    path='/search/:searchQuery?'
                                    render={(props) => (
                                        <SearchPage
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
                        </AppLayout>
                    </MusicContextProvider>
                </Switch>
            </BrowserRouter>
        );
    }
}
