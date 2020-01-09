import React, { Component } from 'react';
import '/stylus/main.styl';
import 'react-toastify/dist/ReactToastify.css';
import Sidebar from './components/Sidebar';
import { User } from '../logic/User';
import Header from './components/Header';
import ReactLoading from 'react-loading';
import MusicControl from './components/MusicControl';

interface AppViewProps {
    user: User;
}

interface AppViewStates {
    error: Error;
}

class AppView extends Component<AppViewProps, AppViewStates> {
    constructor(props) {
        super(props);
        this.state = { error: null };
    }

    componentDidCatch(error: Error, errorInfo) {
        console.log('EERERRR');
        //TODO: Server log?
    }

    static getDerivedStateFromError(error) {
        // Update state so the next render will show the fallback UI.
        console.log('DERIVED', error);
        return { error };
    }

    render() {
        if (this.state.error) {
            return <span>An Error Occured: {this.state.error.message}</span>;
        }

        if (this.props.user == null) {
            return <ReactLoading color='#FF9D00' type={'bubbles'} />;
        }

        return (
            <>
                <Header username={this.props.user.username} />
                <div className='container'>
                    <Sidebar />
                    <div className='content'>{this.props.children}</div>
                </div>
                <MusicControl />
            </>
        );
    }
}

export default AppView;
