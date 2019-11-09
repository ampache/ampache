import React from 'react'
import {User} from "logic/User";

interface AccountProps {
    user: User;
}

export default class AccountView extends React.Component<AccountProps, any> {

    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div className='userPage'>
                <div>Username - {this.props.user.username}</div>
                <div>User ID - {this.props.user.id}</div>
            </div>
        );
    }
}