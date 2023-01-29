import React, { memo } from 'react';
import SVG from 'react-inlinesvg';
import { useFlagItem } from '~logic/Methods/Flag';
import { ItemType } from '~types';
import { Rating } from '@mui/material';
import { useRateItem } from '~logic/Rate';
import { toast } from 'react-toastify';
import starFullIcon from '~images/icons/svg/star-full.svg';
import starEmptyIcon from '~images/icons/svg/star-empty.svg';
import cancelIcon from '~images/icons/svg/cancel.svg';
import heartFullIcon from '~images/icons/svg/heart-full.svg';
import heartEmptyIcon from '~images/icons/svg/heart-empty.svg';

import * as style from './index.styl';

interface SimpleRatingProps {
    value: number;
    fav: boolean;
    itemId: string;
    type: ItemType;
}

const fullIcon = (
    <SVG
        className={`icon ${style.starIcon} ${style.active}`}
        src={starFullIcon}
    />
);
const emptyIcon = (
    <SVG
        className={`icon ${style.starIcon}`}
        src={starEmptyIcon}
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
                src={cancelIcon}
                title='Remove rating'
                onClick={(e) => {
                    handleRating(e, null);
                }}
                className={`icon ${style.cancelIcon}`}
            />
            <Rating
                name={`${type}-${itemId}`}
                value={value}
                icon={fullIcon}
                emptyIcon={emptyIcon}
                onChange={handleRating}
                onClick={(e) => e.stopPropagation()}
            />
            <SVG
                src={fav ? heartFullIcon : heartEmptyIcon}
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
