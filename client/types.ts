import { UseQueryOptions } from '~node_modules/react-query';
import AmpacheError from '~logic/AmpacheError';

export type ItemType =
    | 'song'
    | 'album'
    | 'artist'
    | 'playlist'
    | 'podcast'
    | 'podcast_episode'
    | 'video'
    | 'tvshow'
    | 'tvshow_season';

export type OptionType<T> = Omit<
    UseQueryOptions<T, Error | AmpacheError, T, Array<string>>,
    'queryKey' | 'queryFn'
>;
