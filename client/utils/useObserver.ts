import { useCallback, useEffect, useRef } from 'react';
import { InfiniteQueryObserverBaseResult } from '@tanstack/react-query';

export const useObserver = ({
    isFetching,
    fetchNextPage,
    hasNextPage
}: Pick<
    InfiniteQueryObserverBaseResult,
    'isFetching' | 'fetchNextPage' | 'hasNextPage'
>) => {
    const observerElem = useRef(null);

    const handleObserver = useCallback(
        (entries) => {
            const [target] = entries;
            if (target.isIntersecting && !isFetching) {
                fetchNextPage();
            }
        },
        [fetchNextPage, isFetching]
    );

    useEffect(() => {
        const element = observerElem.current;

        if (!element) return;
        const observer = new IntersectionObserver(handleObserver, {
            threshold: 1
        });
        observer.observe(element);
        return () => observer.unobserve(element);
    }, [fetchNextPage, hasNextPage, handleObserver]);

    return { observerElem };
};
