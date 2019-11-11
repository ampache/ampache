import React from 'react';
import '/stylus/main.styl';
import Sidebar from './components/Sidebar';
import { User } from '../logic/User';
import Header from './components/Header';

interface AppViewProps {
    user: User;
}

export default class AppView extends React.PureComponent<AppViewProps> {
    render() {
        if (this.props.user == null) {
            return <span>Loading...</span>;
        }

        return (
            <>
                <Header username={this.props.user.username} />
                <div className='container'>
                    <Sidebar />
                    <div className='content'>{this.props.children}</div>
                </div>
            </>
        );
    }
}
