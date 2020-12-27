import React, { useState } from 'react';
import logo from '~images/ampache-dark.png';
import AmpacheError from '~logic/AmpacheError';

import style from './index.styl';

interface LoginProps {
    handleLogin: (
        apiKey: string,
        username: string
    ) => Promise<void | AmpacheError | Error>;
}

const LoginView: React.FC<LoginProps> = (props) => {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState<Error | AmpacheError>(null);

    const handleSubmit = (e) => {
        e.preventDefault();
        props.handleLogin(username, password).catch((e) => {
            setError(e);
        });
    };

    return (
        <div className={style.loginPage}>
            <div>{error?.message}</div>
            <img src={logo} className={style.logo} alt='Ampache Logo' />
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
                <button className={style.submit}>Submit</button>
            </form>
        </div>
    );
};

export default LoginView;
