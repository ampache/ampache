import React, {Component} from 'react';
import {BrowserRouter, Route, Switch} from 'react-router-dom';
import App from './Views/App'
import Home from './Views/Home'
import NotFound from './Views/404'

//TODO, fix any
export default class Root extends Component<any, any> {

    constructor(props) {
        super(props);

        this.state = {};
    }

    render() {
        return (
            <BrowserRouter>
                <App>
                    <Switch>
                        <Route exact path="/" render={(props) => <Home {...props} />}/>

                        <Route path="*" render={(props) => <NotFound {...props} />}/> {/*404*/}
                    </Switch>
                </App>
            </BrowserRouter>
        );
    }
}
