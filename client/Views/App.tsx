import React, {Component} from 'react';
import { NavLink, Link } from 'react-router-dom';
import '../stylus/main.styl'

export default class AppView extends Component {
    constructor(props) {
        super(props);
        this.state = {}
    }

    render() {
        return (
            <div className='main'>
                <header>
                    <Link className='title' to="/">
                        <div>AMPACHE</div>
                    </Link>
                </header>
                {this.props.children}
            </div>
        );
    }
}
