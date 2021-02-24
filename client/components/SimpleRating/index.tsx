import React from 'react';
import Rating from '@material-ui/lab/Rating';
import { withStyles } from '@material-ui/core/styles';
import SVG from 'react-inlinesvg';

import style from './index.styl';
import useTraceUpdate from '~Debug/useTraceUpdate';

interface SimpleRatingProps {
    value: number;
    fav: boolean;
    itemID: string;
    setFlag: (id: string, newValue: boolean) => void;
}

const SimpleRating: React.FC<SimpleRatingProps> = (props) => {
    const [value, setValue] = React.useState(props.value);

    const StyledRating = withStyles({
        icon: {
            color: 'currentColor'
        }
    })(Rating); //TODO: Put this into index.styl for consistency
    useTraceUpdate(props, `Simple Rating ${props.itemID}`);

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
            <SVG
                src={
                    props.fav
                        ? require('~images/icons/svg/heart-full.svg')
                        : require('~images/icons/svg/heart-empty.svg')
                }
                title='Toggle favorite'
                aria-label={props.fav ? 'Favorited' : 'Not Favorited'}
                onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    props.setFlag(props.itemID, !props.fav);
                }}
                className={`icon ${style.heartIcon} ${
                    props.fav ? style.active : null
                }`}
            />
        </div>
    );
};

export default SimpleRating;
