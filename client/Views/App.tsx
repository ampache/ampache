import React, {Component} from 'react';
import { NavLink, Link } from 'react-router-dom';
import '/stylus/main.styl'
import logo from '/images/ampache-dark.png'
import handshake from "../logic/Auth";
import {Simulate} from "react-dom/test-utils";
import error = Simulate.error;

export default class AppView extends Component<any, any> {
    constructor(props) {
        super(props);
        this.state = {
            user: {
                role: "Administrator",
                messageCount: 0
            }
        }
    }

    render() {
        return (
            <div className='main'>
                <header>
                    <div className='logoUser'>
                        <Link className='logo' to='/'>
                            <img src={logo} alt='Ampache Logo' />
                        </Link>
                        <span>
                            <Link class='userRole' to='/stats.php?action=show_user&user_id=1'>{this.state.user.role}</Link>
                            <Link class='messageCount' to='/browse.php?action=pvmsg' title="New Messages">({this.state.user.messageCount})</Link>
                        </span>
                    </div>
                    <div className='search'>
                        <form>
                            <input placeholder='Search' name='rule_1_input' autoComplete='off'/>
                            <select>
                                <option>Anywhere</option>
                            </select>
                            <button className='button'>Search</button>
                        </form>
                    </div>
                </header>
                {this.props.children}
            </div>
        );
    }
}
