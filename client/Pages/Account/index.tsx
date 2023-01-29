import React, { useEffect, useState } from 'react';
import { getUser, User } from '~logic/User';
import { cleanCatalog, updateCatalog } from '~logic/Catalog';
import { toast } from 'react-toastify';

import * as style from './index.styl';

interface AccountPageProps {
    user: User;
}

const AccountPage: React.FC<AccountPageProps> = (props) => {
    const [fullName, setFullName] = useState(''); //TODO: https://github.com/ampache/ampache/issues/2234
    const [email, setEmail] = useState(''); //TODO: https://github.com/ampache/ampache/issues/2234
    const [website, setWebsite] = useState(''); //TODO: https://github.com/ampache/ampache/issues/2234
    const [catalogID, setCatalogID] = useState(null);

    useEffect(() => {
        getUser(props.user.username, props.user.authKey).then((userInfo) => {
            setWebsite(userInfo.website);
            setEmail(userInfo.email);
        });
    });

    const handleCatalogUpdate = async () => {
        try {
            await updateCatalog(catalogID, props.user.authKey);
        } catch (err) {
            toast.error('😞 Something went wrong updating Catalog.');
            console.error(err);
            return;
        }

        toast.success('Started Catalog Update.');
    };

    const handleCatalogClean = async () => {
        try {
            await cleanCatalog(catalogID, props.user.authKey);
        } catch (err) {
            toast.error('😞 Something went wrong cleaning Catalog.');
            console.error(err);
            return;
        }

        toast.success('Started Catalog Cleaning.');
    };

    return (
        <div>
            <h1>Edit Account Settings</h1>
            <div>Username - {props.user.username}</div>
            <div>User ID - {props.user.id}</div>
            <div className={style.accountInfo}>
                <h2>Profile</h2>
                <form>
                    <label htmlFor='fullName'>Full Name</label>
                    <input
                        placeholder='Full Name'
                        value={fullName}
                        id='fullName'
                        onChange={(event) => setFullName(event.target.value)}
                    />
                    <label htmlFor='email'>Email</label>
                    <input
                        placeholder='Email'
                        value={email}
                        id='email'
                        onChange={(event) => setEmail(event.target.value)}
                    />
                    <label htmlFor='website'>Website</label>
                    <input
                        placeholder='Website'
                        value={website}
                        id='website'
                        onChange={(event) => setWebsite(event.target.value)}
                    />
                </form>
            </div>
            <div>
                <input
                    placeholder='Catalog ID'
                    value={catalogID ?? ''}
                    onChange={(event) => setCatalogID(event.target.value)}
                />
                <button onClick={handleCatalogUpdate}>Update Catalog</button>
                <button onClick={handleCatalogClean}>Clean Catalog</button>
            </div>
        </div>
    );
};

export default AccountPage;
