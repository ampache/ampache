import React, { useState } from 'react';
import { User } from '~logic/User';
import { updateCatalog } from '~logic/Catalog';
import { toast } from 'react-toastify';

interface AccountProps {
    user: User;
}

const AccountView: React.FC<AccountProps> = (props) => {
    const [fullName, setFullName] = useState(''); //TODO: https://github.com/ampache/ampache/issues/2234
    const [email, setEmail] = useState(''); //TODO: https://github.com/ampache/ampache/issues/2234
    const [website, setWebsite] = useState(''); //TODO: https://github.com/ampache/ampache/issues/2234
    const [catalogID, setCatalogID] = useState(null);

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

    return (
        <div className='accountPage'>
            <h1>Edit Account Settings</h1>
            <div>Username - {props.user.username}</div>
            <div>User ID - {props.user.id}</div>
            <div className='accountInfo'>
                <h2>Profile</h2>
                <form>
                    <input
                        placeholder='Full Name'
                        value={fullName}
                        onChange={(event) => setFullName(event.target.value)}
                    />
                    <input
                        placeholder='Email'
                        value={email}
                        onChange={(event) => setEmail(event.target.value)}
                    />
                    <input
                        placeholder='Website'
                        value={website}
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
            </div>
        </div>
    );
};

export default AccountView;
