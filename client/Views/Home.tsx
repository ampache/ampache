import React from 'react'
export default class HomeView extends React.Component {

    constructor(props) {
        super(props);
    }

    render() {
        console.log(this.props);
        return (
            <div className='homePage'>
                <span>Home Page</span>
            </div>
        );
    }
}