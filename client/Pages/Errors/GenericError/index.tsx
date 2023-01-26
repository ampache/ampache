import React from 'react';

import style from './index.styl';
import { Button } from '@mui/material';

interface GenericErrorInterface {
    message: string;
}

const GenericError: React.FC<GenericErrorInterface> = (props) => {
    return (
        <div className={style.genericError}>
            <div className={style.giantSmiley}> :(</div>
            <div className={style.errorMessage}>
                Sorry, something went wrong...
                <div>{props.message}</div>
            </div>
            <a href='/' className={style.countryRoads}>
                <Button>Take Me Home</Button>
            </a>
        </div>
    );
};

export default GenericError;
