import React from 'react';
import Rating from '@material-ui/lab/Rating';
import { withStyles } from '@material-ui/core/styles';
import SVG from 'react-inlinesvg';

import style from './index.module.styl';

export default function SimpleRating(props) {
    const [value, setValue] = React.useState(3);

    const StyledRating = withStyles({
        icon: {
          color: 'currentColor',
        }
      })(Rating);

    return (
        <div className={style.ratings}>
            <SVG
                src={require('~images/icons/svg/cancel.svg')}
                alt='Remove rating'
                onClick={() => {
                    // TODO: remove rating;
                }}
                className={`${'icon'} ${style.cancelIcon}`}
            />
            <StyledRating
                className={style.simpleRating}
                name="simple-controlled"
                value={props.value}
                icon={<SVG className={`${'icon'} ${style.starIcon} ${style.active}`} src={require('~images/icons/svg/star-full.svg')} />}
                emptyIcon={<SVG className={`${'icon'} ${style.starIcon}`} src={require('~images/icons/svg/star-empty.svg')} />}
                onChange={(event, newValue) => {
                    setValue(newValue);
                }}
            />
            <span className={style.divider}></span>
            <SVG
                src={props.fav ? require('~images/icons/svg/heart-full.svg') : require('~images/icons/svg/heart-empty.svg')}
                alt='Toggle favorite'
                onClick={() => {
                    // TODO: toggle favourite;
                }}
                className={`${'icon'} ${style.heartIcon} ${props.fav ? style.active : null}`}
            />
        </div>
    );
}