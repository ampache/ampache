import React from 'react';
import '/stylus/main.styl';
import Sidebar from './components/Sidebar';
import { User } from '../logic/User';
import Header from './components/Header';

interface AppViewProps {
    user: User;
}

const AppView: React.FunctionComponent<AppViewProps> = (props) => {
    if (props.user == null) {
        return <span>Loading...</span>;
    }

    return (
        <>
            <Header username={props.user.username} />
            <div className='container'>
                <Sidebar />
                <div className='content'>{props.children}</div>
            </div>
        </>
    );
};

export default AppView;
