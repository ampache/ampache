/* eslint-disable immutable/no-mutation */
import React, { Component } from 'react';
import '/stylus/main.styl';
import 'react-toastify/dist/ReactToastify.css';
import Sidebar from '~components/Sidebar';
import { User } from '~logic/User';
import Header from '~components/Header';
import ReactLoading from 'react-loading';
import MusicControl from '~components/MusicControl/';
import QueueBar from '~components/QueueBar/';

import style from './index.styl';

interface AppLayoutProps {
    user: User;
}

interface AppLayoutStates {
    QueueBarVisible: boolean;
    SideBarVisible: boolean;
}

class AppLayout extends Component<AppLayoutProps, AppLayoutStates> {
    private readonly toggleQueueBarVisible: () => void;
    private readonly setQueueBarVisibility: (visible: boolean) => void;
    private readonly toggleSideBarVisible: () => void;
    private readonly setSideBarVisibility: (visible: boolean) => void;

    constructor(props) {
        super(props);
        this.state = {
            QueueBarVisible: false,
            SideBarVisible: false
        };

        this.toggleQueueBarVisible = () => {
            this.setState({ QueueBarVisible: !this.state.QueueBarVisible });
        };
        this.setQueueBarVisibility = (visible: boolean) => {
            this.setState({ QueueBarVisible: visible });
        };

        this.toggleSideBarVisible = () => {
            this.setState({ SideBarVisible: !this.state.SideBarVisible });
        };
        this.setSideBarVisibility = (visible: boolean) => {
            this.setState({ SideBarVisible: visible });
        };
    }

    render() {
        if (this.props.user == null) {
            return <ReactLoading color='#FF9D00' type={'bubbles'} />;
        }

        return (
            <>
                <Header
                    username={this.props.user.username}
                    toggleQueueBar={this.toggleQueueBarVisible}
                    toggleSideBar={this.toggleSideBarVisible}
                />
                <div className={style.container}>
                    <Sidebar
                        visible={this.state.SideBarVisible}
                        setSideBarVisibility={this.setSideBarVisibility}
                    />
                    <div className={style.content}>
                        <div className={style.contentInner}>
                            {this.props.children}
                        </div>
                    </div>

                    <QueueBar
                        visible={this.state.QueueBarVisible}
                        setQueueBarVisibility={this.setQueueBarVisibility}
                    />
                </div>
                <MusicControl />
            </>
        );
    }
}

export default AppLayout;
