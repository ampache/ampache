import React, { ComponentState } from 'react';

interface LoginState {
    username: string;
    password: string;
}

interface LoginProps {
    handleLogin: (apiKey: string, username: string) => void;
}

export default class HomeView extends React.PureComponent<
    LoginProps,
    LoginState
> {
    constructor(props) {
        super(props);

        this.state = {
            username: '',
            password: ''
        };

        this.handleSubmit = this.handleSubmit.bind(this);
        this.handleInputChange = this.handleInputChange.bind(this);
    }

    handleSubmit(e) {
        e.preventDefault();
        this.props.handleLogin(this.state.username, this.state.password);
    }

    handleInputChange(event) {
        const target = event.target;
        const value = target.value;
        const name = target.name;

        this.setState({
            [name]: value
        } as ComponentState);

        this.handleInputChange = this.handleInputChange.bind(this);
    }

    render() {
        return (
            <div className='loginPage'>
                <form onSubmit={this.handleSubmit}>
                    <input
                        placeholder='Username'
                        name='username'
                        value={this.state.username}
                        onChange={this.handleInputChange}
                    />
                    <input
                        placeholder='Password'
                        name='password'
                        value={this.state.password}
                        onChange={this.handleInputChange}
                    />
                    <button className='submit'>Submit</button>
                </form>
            </div>
        );
    }
}
