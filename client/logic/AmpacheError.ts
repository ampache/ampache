export default class AmpacheError extends Error {
    constructor(error: { code: number; message: string }) {
        super();
        this.message = error.message;
        this.code = error.code;
    }
    code: number;
    message: string;
}
