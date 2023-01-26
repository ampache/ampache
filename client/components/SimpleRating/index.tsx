import React from 'react';
import SVG from 'react-inlinesvg';

import style from './index.styl';
import { useFlagItem } from '~logic/Methods/Flag';
import { ItemType } from '~types';
import { Rating } from '@mui/lab';
import { rateItem } from '~logic/Rate';
import { useQueryClient } from 'react-query';
import { toast } from 'react-toastify';

interface SimpleRatingProps {
    value: number;
    fav: boolean;
    itemID: string;
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

const SimpleRating = (props: SimpleRatingProps) => {
    const { fav, itemID, type, value } = props;
    const queryClient = useQueryClient();
    const flagItem = useFlagItem(type, itemID);

    const handleRating = (event, newValue: number | null) => {
        //null when the same rating is clicked, as in to remove it, so 0
        const rating = newValue ?? 0;
        event.preventDefault();
        event.stopPropagation();
        queryClient.setQueryData([type, itemID], (input: any) => {
            return {
                ...input,
                rating
            };
        });
        rateItem(itemID, type, rating).catch(() => {
            toast.error('Failed to set rating');
            queryClient.setQueryData([type, itemID], (input: any) => {
                return {
                    ...input,
                    rating: value
                };
            });
        });
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
                name={`${type}-${itemID}`}
                value={value}
                icon={fullIcon}
                emptyIcon={emptyIcon}
                onChange={handleRating}
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
                        objectID: itemID,
                        favorite: !fav
                    });
                }}
                className={`icon ${style.heartIcon} ${
                    fav ? style.active : null
                }`}
            />
        </div>
    );
};

export default SimpleRating;
