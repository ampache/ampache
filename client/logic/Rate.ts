import { ampacheClient } from '~main';
import { ItemType } from '~types';

export const rateItem = (itemId: string, type: ItemType, newRating: number) => {
    return ampacheClient.get('', {
        params: {
            action: 'rate',
            type,
            id: itemId,
            rating: newRating,
            version: '6.0.0'
        }
    });
};
