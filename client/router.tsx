import React from 'react';
import { BrowserRouter, Route, Switch, Redirect } from 'react-router-dom';
import Cookies from 'js-cookie';
import { Howler } from 'howler';

import App from './Views/App';
import Home from './Views/Home/';
import Account from './Views/Account/';
import SearchView from './Views/Search/';
import LoginView from './Views/Login/';
import NotFound from './Views/404/';
import handshake, { AuthKey } from './logic/Auth';
import { getUser, User } from './logic/User';
import AlbumView from './Views/Album';
import ArtistView from './Views/Artist';
import { MusicContextProvider } from './MusicContext';

interface RouterState {
    authKey: AuthKey;
    username: string;
    user: User;
}

const server = 'http://localhost:8080';
export default class Root extends React.PureComponent<void, RouterState> {
    constructor(props) {
        super(props);

        this.state = {
            authKey: Cookies.get('authKey'),
            username: Cookies.get('username'),
            user: null
        };

        this.handleLogin = this.handleLogin.bind(this);
        this.handleLogout = this.handleLogout.bind(this);
    }

    componentDidMount() {
        if (this.state.authKey != null) {
            getUser(this.state.username, this.state.authKey, server)
                .then((user: User) => {
                    user.authKey = this.state.authKey;
                    this.setState({ user });
                })
                .catch((error) => {
                    console.error('GETUSERFAILED', error); //TODO: Error handling
                    // this.handleLogout();
                });
        }
    }

    private handleLogin(username: string, password: string) {
        handshake(username, password, server)
            .then((newAuthKey: AuthKey) => {
                this.setState({ authKey: newAuthKey });
                Cookies.set('authKey', newAuthKey);
                Cookies.set('username', username);
                getUser(username, newAuthKey, server)
                    .then((user: User) => {
                        user.authKey = newAuthKey;
                        this.setState({ user });
                    })
                    .catch((error) => {
                        console.error('HANDSHAKE-GETUSERFAILED', error); //TODO: Error handling
                    });
            })
            .catch((error) => {
                console.error('LOGINFAILED', error); //TODO: Error handling
            });
    }

    private handleLogout() {
        Howler.unload();
        Cookies.remove('authKey');
        Cookies.remove('username');
        this.setState({ authKey: null, username: null, user: null });
    }

    render() {
        if (this.state.authKey == null) {
            return (
                <BrowserRouter>
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
            <BrowserRouter>
                <Switch>
                    {' '}
                    //TODO: Do i need this switch?
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
                                    path='/search/:searchQuery'
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
                                />{' '}
                            </Switch>
                        </App>
                    </MusicContextProvider>
                </Switch>
            </BrowserRouter>
        );
    }
}
