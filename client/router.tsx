import React, {Component} from 'react';
import {BrowserRouter, Route, Switch, Redirect} from 'react-router-dom';
import Cookies from 'js-cookie'

import App from './Views/App'
import Home from './Views/Home/'
import Account from './Views/Account/'
import Login from './Views/Login/'
import NotFound from './Views/404/'
import handshake from "./logic/Auth";
import {getUser, User} from './logic/User';
import AlbumView from "./Views/Album";

//TODO, fix any
interface RouterState {
    authCode: string;
    username: string,
    user: object;
}

const server = "http://localhost:8080";
export default class Root extends Component<any, RouterState> {

    constructor(props) {
        super(props);

        this.state = {
            authCode: Cookies.get('authCode'),
            username: Cookies.get('username'),
            user: null
        };

        if(this.state.authCode != null) {
            getUser(this.state.username, this.state.authCode, server).then((user: User) => {
                user.authCode = this.state.authCode;
                this.setState({user});
            }).catch((error) => {
                console.error("GETUSERFAILED", error); //TODO: Error handling
            })
        }

        this.handleLogin = this.handleLogin.bind(this);
        this.handleLogout = this.handleLogout.bind(this);
    }


    private handleLogin(username: string, password: string) {
        handshake(username, password, server).then((authCode: string) => {
            this.setState({authCode});
            Cookies.set('authCode', authCode);
            Cookies.set('username', username);
            getUser(username, authCode, server).then((user: User) => {
                user.authCode = authCode;
                this.setState({user});
                console.log(user);
            }).catch((error) => {
                console.error("GETUSERFAILED", error); //TODO: Error handling
            })
        }).catch((error) => {
            console.error("LOGINFAILED", error); //TODO: Error handling
        });
    }

    private handleLogout() {
        Cookies.remove('authCode');
        Cookies.remove('username');
        this.setState({authCode: null, username: null, user: null})
    }

    render() {
        if (this.state.authCode == null) {
            return (
                <BrowserRouter>
                    <Route render={(props) => <Login {...props} handleLogin={this.handleLogin}/>}/>
                </BrowserRouter>);
        }

        return (
            <BrowserRouter>
                <App user={this.state.user}>
                    <Switch>
                        <Route exact path="/" render={(props) => <Home {...props} user={this.state.user} />}/>
                        <Route exact path="/account" render={(props) => <Account {...props} user={this.state.user} />}/>
                        <Route exact path="/album/:albumID" render={(props) => <AlbumView {...props} user={this.state.user} />}/>
                        <Route exact path="/login">
                            <Redirect to='/'/>
                        </Route>
                        <Route exact path='/logout' render={() => {this.handleLogout(); return(<Redirect to='/login'/>) }  }>

                        </Route>
                        <Route path="*" render={(props) => <NotFound {...props} />}/> {/*404*/}
                    </Switch>
                </App>
            </BrowserRouter>
        );
    }
}
