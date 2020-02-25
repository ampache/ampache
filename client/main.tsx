import React from 'react';
import ReactDOM from 'react-dom';
import Root from './router';
import { toast } from 'react-toastify';

const render = (Component) => {
    toast.configure();
    ReactDOM.render(
        <React.StrictMode>
            <Component />
        </React.StrictMode>,
        document.getElementById('root')
    );
};

render(Root);
