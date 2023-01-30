import React, { useState } from 'react';
import logo from '~images/ampache-dark.png';
import ReactLoading from 'react-loading';
import { useLogin } from '~logic/Auth';

import * as style from './index.styl';

const LoginPage = () => {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');

    const { mutate: login, error, isLoading } = useLogin();

    const handleSubmit = (e) => {
        e.preventDefault();
        login({ username, password });
    };

    if (isLoading) {
        return <ReactLoading />;
    }

    return (
        <div className={style.loginPage}>
            <div>{error?.message}</div>
            <img src={logo} alt='Ampache Logo' />
            <form onSubmit={handleSubmit}>
                <label htmlFor='username'>Username</label>
                <input
                    placeholder='Username'
                    name='username'
                    id='username'
                    value={username}
                    onChange={(event) => setUsername(event.target.value)}
                />
                <label htmlFor='password'>Password</label>
                <input
                    placeholder='Password'
                    type='password'
                    name='password'
                    id='password'
                    value={password}
                    onChange={(event) => setPassword(event.target.value)}
                />
                <button>Submit</button>
            </form>
        </div>
    );
};

export default LoginPage;
