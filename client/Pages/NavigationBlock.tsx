import React, { useEffect } from 'react';
import { useHistory } from 'react-router-dom';

interface NavigationBlockProps {
    enabled: boolean;
    navigationAttempt: () => void;
    children: any;
}

const NavigationBlock = (props: NavigationBlockProps) => {
    const history = useHistory();

    const { enabled, navigationAttempt } = { ...props };

    useEffect(() => {
        if (enabled) {
            const unblock = history.block(() => {
                navigationAttempt();
                return false;
            });
            return () => {
                unblock();
            };
        }
    }, [enabled, history, navigationAttempt]);

    return props.children;
};

export default NavigationBlock;
