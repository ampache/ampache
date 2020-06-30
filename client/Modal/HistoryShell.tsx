import React, { cloneElement, useEffect } from 'react';

interface ConfirmationModalParams {
    children?: any;
    history: any;
    ok?: any; //TODO
    cancel?: any;
}

const HistoryShell = (props: ConfirmationModalParams) => {
    const { children, history, ok, cancel } = { ...props };

    useEffect(() => {
        const unblock = history.block(() => {
            cancel();
            return false;
        });
        return () => {
            unblock();
        };
    }, [cancel, history]);

    if (children) {
        return cloneElement(children, { ok, cancel });
    }
    return null;
};

export default HistoryShell;
