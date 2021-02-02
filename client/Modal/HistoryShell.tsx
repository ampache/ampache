import React, { cloneElement, useEffect } from 'react';

interface ConfirmationModalParams {
    children?: any;
    history: any;
    ok?: any; //TODO
    cancel?: any;
    blocked?: boolean;
}

const HistoryShell = (props: ConfirmationModalParams) => {
    const { children, history, ok, cancel } = { ...props };

    useEffect(() => {
        //Allows the back button to close the modal
        const unblock = history.block((tx) => {
            console.log(tx);
            cancel();
            return false;
        }); //TODO: This causes the forward and back buttons to close the modal, not sure if desirable.
        return () => {
            if (props.blocked) {
                console.log('unblock');
                unblock();
            }
        };
    }, [props.blocked, cancel, history]);

    if (children) {
        return cloneElement(children, { ok, cancel });
    }
    return null;
};

export default HistoryShell;
