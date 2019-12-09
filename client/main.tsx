import { AppContainer } from 'react-hot-loader';
import React from 'react';
import ReactDOM from 'react-dom';
import Root from './router';
import { toast } from 'react-toastify';

const render = (Component) => {
    toast.configure();
    ReactDOM.render(
        <AppContainer>
            <React.StrictMode>
                <Component />
            </React.StrictMode>
        </AppContainer>,
        document.getElementById('root')
    );
};

render(Root);
