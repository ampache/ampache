import React, { cloneElement, useEffect } from 'react';

interface ConfirmationModalParams {
    children?: any;
    history: any;
    ok?: any; //TODO
    cancel?: any;
}

const HistoryShell = (props: ConfirmationModalParams) => {
    const { children, history, ok, cancel } = { ...props };

    useEffect(() => { //Allows the back button to close the modal
        const unblock = history.block(() => {
            cancel();
            return false;
        }); //TODO: This causes the forward and back buttons to close the modal, not sure if desirable.
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
