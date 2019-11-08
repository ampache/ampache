import React, {Component} from 'react';
import {BrowserRouter, Route, Switch, Redirect} from 'react-router-dom';
import Cookies from 'js-cookie'

import App from './Views/App'
import Home from './Views/Home'
import Login from './Views/Login'
import NotFound from './Views/404'
import handshake from "./logic/Auth";

//TODO, fix any
interface RouterState {
    authCode: string;
}

export default class Root extends Component<RouterState, any> {

    constructor(props) {
        super(props);

        this.state = {
            authCode: null
        };

        this.handleLogin = this.handleLogin.bind(this);
    }


    private handleLogin(username, password) {
        handshake(username, password, "http://localhost:8080").then((authCode) => {
            this.setState({authCode});

            Cookies.set('authCode', authCode)
        }).catch((error) =>{
            console.error("LOGINFAILED", error);
        });
    }


    render() {
        if (this.state.authCode == null) {
            console.log("LOGIN", this.state.authCode);
            return (
                <BrowserRouter>
                    <Route render={(props) => <Login {...props} handleLogin={this.handleLogin} />}/>
                </BrowserRouter>);
        }

        return (
            <BrowserRouter>
                <App>
                    <Switch>
                        <Route exact path="/" render={(props) => <Home {...props} />}/>
                        <Route exact path="/login">
                            <Redirect to='/'/>
                        </Route>
                        <Route path="*" render={(props) => <NotFound {...props} />}/> {/*404*/}
                    </Switch>
                </App>
            </BrowserRouter>
        );
    }
}
