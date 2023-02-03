import React from 'react';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import AppLayout from './Layouts/App/';
import HomePage from './Pages/Home/';
import AccountPage from './Pages/Account/';
import SearchPage from './Pages/Search/';
import LoginPage from './Pages/Login/';
import NotFound from './Pages/Errors/404/';
import { useGetUser } from '~logic/User';
import AlbumPage from './Pages/Album';
import AlbumsPage from './Pages/Albums';
import ArtistPage from './Pages/Artist';
import { MusicContextProvider } from '~Contexts/MusicContext';
import ArtistsPage from './Pages/Artists';
import PlaylistsPage from './Pages/Playlists';
import PlaylistPage from './Pages/Playlist';
import GenericError from '~Pages/Errors/GenericError';
import ampacheLogo from './images/ampache-dark.png';

import style from './stylus/router.styl';
import { FavoritesPage } from '~Pages/Favorites';

export const Root = () => {
    const { data: user, isLoading, error } = useGetUser();

    if (error) {
        return <GenericError message={error.message} />;
    }

    if (isLoading) {
        return (
            <div className={style.appLoading}>
                <img src={ampacheLogo} alt={'loading animation'} />
            </div>
        );
    }

    if (!user) {
        return (
            <BrowserRouter basename='/client'>
                <Routes>
                    <Route path={'*'} element={<LoginPage />}></Route>
                </Routes>
            </BrowserRouter>
        );
    }

    return (
        <BrowserRouter basename='/client'>
            <MusicContextProvider>
                <AppLayout user={user}>
                    <Routes>
                        <Route index element={<HomePage />} />
                        <Route
                            path='/account'
                            element={<AccountPage user={user} />}
                        />
                        <Route path='/album/:albumID' element={<AlbumPage />} />
                        <Route path='/albums' element={<AlbumsPage />} />
                        <Route
                            path='/artist/:artistID'
                            element={<ArtistPage />}
                        />
                        <Route path='/artists' element={<ArtistsPage />} />
                        <Route path='/playlists' element={<PlaylistsPage />} />
                        <Route path='/favorites' element={<FavoritesPage />} />
                        <Route
                            path='/playlist/:playlistID'
                            element={<PlaylistPage />}
                        />
                        <Route
                            path='/search/:searchQuery?'
                            element={<SearchPage />}
                        />
                        <Route path='*' element={<NotFound />} />
                    </Routes>
                </AppLayout>
            </MusicContextProvider>
        </BrowserRouter>
    );
};
