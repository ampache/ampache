import React, { useState } from 'react';
import { User } from 'logic/User';

interface AccountProps {
    user: User;
}

const AccountView: React.FC<AccountProps> = (props) => {
    const [fullName, setFullName] = useState(''); //TODO: https://github.com/ampache/ampache/issues/2234
    const [email, setEmail] = useState(''); //TODO: https://github.com/ampache/ampache/issues/2234
    const [website, setWebsite] = useState(''); //TODO: https://github.com/ampache/ampache/issues/2234

    return (
        <div className='accountPage'>
            <h1>Edit Account Settings</h1>
            <div>Username - {props.user.username}</div>
            <div>User ID - {props.user.id}</div>
            <div className='accountInfo'>
                <h2>Profile</h2>
                <form>
                    <input
                        placeholder='Full Name'
                        value={fullName}
                        onChange={(event) => setFullName(event.target.value)}
                    />
                    <input
                        placeholder='Email'
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                    />
                    <input
                        placeholder='Website'
                        value={website}
                        onChange={(event) => setWebsite(event.target.value)}
                    />
                </form>
            </div>
        </div>
    );
};

export default AccountView;
