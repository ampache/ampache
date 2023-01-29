import React, { useState } from 'react';
import logo from '~images/ampache-dark.png';
import AmpacheError from '~logic/AmpacheError';

import * as style from './index.styl';
import ReactLoading from 'react-loading';

interface LoginPageProps {
    handleLogin: (
        apiKey: string,
        username: string
    ) => Promise<void | AmpacheError | Error>;
}

const LoginPage: React.FC<LoginPageProps> = (props) => {
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<Error | AmpacheError>(null);

    const handleSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        props.handleLogin(username, password).catch((e) => {
            setLoading(false);
            setError(e);
        });
    };

    if (loading) {
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
