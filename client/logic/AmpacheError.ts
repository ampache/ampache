export default class AmpacheError extends Error {
    private errorAction: string;
    private errorCode: string;
    private errorMessage: string;
    private errorType: string;
    constructor(error: {
        errorAction: string;
        errorCode: string; //TODO: Ask if this shouldn't be number
        errorMessage: string;
        errorType: string;
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
