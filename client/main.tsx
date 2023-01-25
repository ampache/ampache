import React from 'react';
import ReactDOM from 'react-dom';
import Root from './router';
import { toast } from 'react-toastify';
import axios from 'axios';
import AmpacheError from '~logic/AmpacheError';
import { QueryClient, QueryClientProvider } from 'react-query';
import Cookies from 'js-cookie';

export const ampacheClient = axios.create({
    baseURL: process.env.ServerURL
});

ampacheClient.interceptors.request.use((config) => {
    // eslint-disable-next-line immutable/no-mutation
    config.params = { ...config.params, auth: Cookies.get('authKey') };
    return config;
});

ampacheClient.interceptors.response.use((res) => {
    if (res.data.error) {
        throw new AmpacheError(res.data.error);
    }
    return res;
});
const queryClient = new QueryClient({
    defaultOptions: { queries: { staleTime: 300000 } }
});

const render = (Component) => {
    toast.configure();
    ReactDOM.render(
        <React.StrictMode>
            <QueryClientProvider client={queryClient}>
                <Component />
            </QueryClientProvider>
        </React.StrictMode>,
        document.getElementById('root')
    );
};

render(Root);
