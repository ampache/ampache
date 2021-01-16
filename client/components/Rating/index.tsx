import React from 'react';
import Rating from '@material-ui/lab/Rating';
import { withStyles } from '@material-ui/core/styles';
import SVG from 'react-inlinesvg';

import style from './index.styl';

//TODO: Props interface

export default function SimpleRating(props) {
    const [value, setValue] = React.useState(props.value);

    const StyledRating = withStyles({
        icon: {
            color: 'currentColor'
        }
    })(Rating);

    return (
        <div className={style.ratings}>
            <SVG
                src={require('~images/icons/svg/cancel.svg')}
                title='Remove rating'
                onClick={() => {
                    // TODO: remove rating;
                }}
                className={`icon ${style.cancelIcon}`}
            />
            <StyledRating
                className={style.simpleRating}
                name='simple-controlled'
                value={value}
                icon={
                    <SVG
                        className={`icon ${style.starIcon} ${style.active}`}
                        src={require('~images/icons/svg/star-full.svg')}
                    />
                }
                emptyIcon={
                    <SVG
                        className={`icon ${style.starIcon}`}
                        src={require('~images/icons/svg/star-empty.svg')}
                    />
                }
                onChange={(event, newValue) => {
                    setValue(newValue);
                }}
            />
            <span className={style.divider} />
            <SVG
                src={
                    props.fav
                        ? require('~images/icons/svg/heart-full.svg')
                        : require('~images/icons/svg/heart-empty.svg')
                }
                title='Toggle favorite'
                onClick={() => {
                    props.flagSong(props.song.id, !props.fav);
                }}
                className={`icon ${style.heartIcon} ${
                    props.fav ? style.active : null
                }`}
            />
        </div>
    );
}
