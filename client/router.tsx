import React from 'react';
import { BrowserRouter, Route, Switch, Redirect } from 'react-router-dom';
import Cookies from 'js-cookie';

import App from './Views/App';
import Home from './Views/Home/';
import Account from './Views/Account/';
import SearchView from './Views/Search/';
import Login from './Views/Login/';
import NotFound from './Views/404/';
import handshake, { AuthKey } from './logic/Auth';
import { getUser, User } from './logic/User';
import AlbumView from './Views/Album';
import MusicPlayer from './Views/MusicPlayer';
import { MusicPlayerContextProvider } from './MusicPlayerContext';

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
                    console.log(this.state.authKey, this.state.username);
                    this.handleLogout();
                });
        }
    }

    private handleLogin(username: string, password: string) {
        handshake(username, password, server)
            .then((authKey: AuthKey) => {
                this.setState({ authKey });
                Cookies.set('authKey', authKey);
                Cookies.set('username', username);
                getUser(username, authKey, server)
                    .then((user: User) => {
                        user.authKey = authKey;
                        this.setState({ user });
                        console.log(user);
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
        Cookies.remove('authKey');
        Cookies.remove('username');
        this.setState({ authKey: null, username: null, user: null });
    }

    render() {
        console.log('rENDER")');
        if (this.state.authKey == null) {
            return (
                <BrowserRouter>
                    <Route
                        render={(props) => (
                            <Login {...props} handleLogin={this.handleLogin} />
                        )}
                    />
                </BrowserRouter>
            );
        }
        return (
            <BrowserRouter>
                <App user={this.state.user}>
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
                        <MusicPlayerContextProvider>
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
                            <MusicPlayer />
                        </MusicPlayerContextProvider>
                    </Switch>
                </App>
            </BrowserRouter>
        );
    }
}
