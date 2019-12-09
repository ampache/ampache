import React, { useState } from 'react';
import logo from '/images/ampache-dark.png';
import AmpacheError from '../../logic/AmpacheError';
import { toast } from 'react-toastify';

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
        <div className='loginPage'>
            <div>{error?.message}</div>
            <img src={logo} className='logo' alt='Ampache Logo' />
            <form onSubmit={handleSubmit}>
                <input
                    placeholder='Username'
                    name='username'
                    value={username}
                    onChange={(event) => setUsername(event.target.value)}
                />
                <input
                    placeholder='Password'
                    type='password'
                    name='password'
                    value={password}
                    onChange={(event) => setPassword(event.target.value)}
                />
                <button className='submit'>Submit</button>
            </form>
        </div>
    );
};

export default LoginView;
