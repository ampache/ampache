import React from 'react';
import Rating from '@material-ui/lab/Rating';
import { withStyles } from '@material-ui/core/styles';
import SVG from 'react-inlinesvg';

import style from './index.module.styl';

export default function SimpleRating() {
    const [value, setValue] = React.useState(3);

    const StyledRating = withStyles({
        icon: {
          color: 'currentColor',
        }
      })(Rating);

    return (
        <div className={style.ratings}>
            <StyledRating
                className={style.simpleRating}
                name="simple-controlled"
                value={value}
                icon={<SVG src={require('~images/icons/svg/star-full.svg')} />}
                emptyIcon={<SVG src={require('~images/icons/svg/star-empty.svg')} />}
                onChange={(event, newValue) => {
                    setValue(newValue);
                }}
            />
        </div>
    );
}