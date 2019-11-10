import React from 'react';
export default class Error404 extends React.Component {
    constructor(props) {
        super(props);
    }

    render() {
        return (
            <div className='error404'>
                <img
                    src='data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/PjxzdmcgaGVpZ2h0PSIzMnB4IiB2ZXJzaW9uPSIxLjEiIHZpZXdCb3g9IjAgMCAzMiAzMiIgd2lkdGg9IjMycHgiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6c2tldGNoPSJodHRwOi8vd3d3LmJvaGVtaWFuY29kaW5nLmNvbS9za2V0Y2gvbnMiIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj48dGl0bGUvPjxkZWZzLz48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGlkPSJJY29ucyBuZXcgQXJyYW5nZWQgTmFtZXMgQ29sb3IiIHN0cm9rZT0ibm9uZSIgc3Ryb2tlLXdpZHRoPSIxIj48ZyBmaWxsPSIjRkYwMDAwIiBpZD0iMTAxIFdhcm5pbmciPjxwYXRoIGQ9Ik0xNC40MjQyMzI3LDYuMTQ4MzkyNzUgQzE1LjI5NDI5ODcsNC43NDA3Mjk3NiAxNi43MDcwMjgsNC43NDQwODQ0MiAxNy41NzUwMjA1LDYuMTQ4MzkyNzUgTDI4LjM2MDEwOTksMjMuNTk3MzggQzI5LjUyMTYzODgsMjUuNDc2NTk1MSAyOC42NzU1NDYyLDI3IDI2LjQ3MTQwNjgsMjcgTDUuNTI3ODQ2NCwyNyBDMy4zMjMyMTU1NywyNyAyLjQ3Mzg2MzE3LDI1LjQ4MjY2NDIgMy42MzkxNDMzMSwyMy41OTczOCBaIE0xNiwyMCBDMTYuNTUyMjg0NywyMCAxNywxOS41NDY5NjM3IDE3LDE5LjAwMjk2OTkgTDE3LDEyLjk5NzAzMDEgQzE3LDEyLjQ0NjM4NTYgMTYuNTU2MTM1MiwxMiAxNiwxMiBDMTUuNDQ3NzE1MywxMiAxNSwxMi40NTMwMzYzIDE1LDEyLjk5NzAzMDEgTDE1LDE5LjAwMjk2OTkgQzE1LDE5LjU1MzYxNDQgMTUuNDQzODY0OCwyMCAxNiwyMCBaIE0xNiwyNCBDMTYuNTUyMjg0OCwyNCAxNywyMy41NTIyODQ4IDE3LDIzIEMxNywyMi40NDc3MTUyIDE2LjU1MjI4NDgsMjIgMTYsMjIgQzE1LjQ0NzcxNTIsMjIgMTUsMjIuNDQ3NzE1MiAxNSwyMyBDMTUsMjMuNTUyMjg0OCAxNS40NDc3MTUyLDI0IDE2LDI0IFogTTE2LDI0IiBpZD0iVHJpYW5nbGUgMjkiLz48L2c+PC9nPjwvc3ZnPg=='
                    className='center'
                    alt='Red Triangle'
                />
                <div className='title'>Error - 404</div>
            </div>
        );
    }
}
