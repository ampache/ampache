import React from 'react';
import SVG from 'react-inlinesvg';

import style from './index.styl';
import { useFlagItem } from '~logic/Methods/Flag';
import { ItemType } from '~types';
import { Rating } from '@material-ui/lab';

interface SimpleRatingProps {
    value: number;
    fav: boolean;
    itemID: string;
    type: ItemType;
}

const SimpleRating: React.FC<SimpleRatingProps> = (props) => {
    const [value, setValue] = React.useState(props.value);
    const flagItem = useFlagItem();

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
            <Rating
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
                        color='#A1A1AA'
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
                    flagItem.mutate({
                        type: props.type,
                        objectID: props.itemID,
                        favorite: !props.fav
                    });
                }}
                className={`icon ${style.heartIcon} ${
                    props.fav ? style.active : null
                }`}
            />
        </div>
    );
};

export default SimpleRating;
