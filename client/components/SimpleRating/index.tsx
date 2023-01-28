import React, { memo } from 'react';
import SVG from 'react-inlinesvg';

import style from './index.styl';
import { useFlagItem } from '~logic/Methods/Flag';
import { ItemType } from '~types';
import { Rating } from '@mui/material';
import { useRateItem } from '~logic/Rate';
import { toast } from 'react-toastify';

interface SimpleRatingProps {
    value: number;
    fav: boolean;
    itemId: string;
    type: ItemType;
}

const fullIcon = (
    <SVG
        className={`icon ${style.starIcon} ${style.active}`}
        src={require('~images/icons/svg/star-full.svg')}
    />
);
const emptyIcon = (
    <SVG
        className={`icon ${style.starIcon}`}
        src={require('~images/icons/svg/star-empty.svg')}
        color='#A1A1AA'
    />
);

// eslint-disable-next-line react/display-name
const SimpleRating = memo((props: SimpleRatingProps) => {
    const { fav, itemId, type, value } = props;
    const flagItem = useFlagItem(type, itemId);
    const rateItem = useRateItem();

    const handleRating = (event, newValue: number | null) => {
        event.preventDefault();
        event.stopPropagation();
        //null when the same rating is clicked, as in to remove it, so 0
        const rating = newValue ?? 0;

        rateItem.mutate(
            { itemId, type, rating },
            {
                onError: (e) => {
                    console.log(e);
                    toast.error('Failed to set rating');
                }
            }
        );
    };

    return (
        <div className={style.ratings}>
            <SVG
                src={require('~images/icons/svg/cancel.svg')}
                title='Remove rating'
                onClick={(e) => {
                    handleRating(e, null);
                }}
                className={`icon ${style.cancelIcon}`}
            />
            <Rating
                className={style.simpleRating}
                name={`${type}-${itemId}`}
                value={value}
                icon={fullIcon}
                emptyIcon={emptyIcon}
                onChange={handleRating}
                onClick={(e) => e.stopPropagation()}
            />
            <SVG
                src={
                    fav
                        ? require('~images/icons/svg/heart-full.svg')
                        : require('~images/icons/svg/heart-empty.svg')
                }
                title='Toggle favorite'
                aria-label={fav ? 'Favorited' : 'Not Favorited'}
                onClick={(e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    flagItem.mutate({
                        type: type,
                        objectID: itemId,
                        favorite: !fav
                    });
                }}
                className={`icon ${style.heartIcon} ${
                    fav ? style.active : null
                }`}
            />
        </div>
    );
});

export default SimpleRating;
