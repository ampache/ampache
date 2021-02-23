/* eslint-disable immutable/no-mutation */
export default class AmpacheError extends Error {
    private errorAction: string;
    private errorCode: string;
    private errorMessage: string;
    private errorType: string;
    constructor(error: {
        errorCode: string;
        errorAction: string;
        errorType: string;
        errorMessage: string;
    }) {
        super();
        this.errorAction = error.errorAction;
        this.errorCode = error.errorCode;
        this.errorMessage = error.errorMessage;
        this.errorType = error.errorType;
        this.message = error.errorMessage;
        this.code = parseInt(error.errorCode);
    }
    code: number;
    message: string;
}
