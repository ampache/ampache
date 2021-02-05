import React from 'react';
import { useSpring, animated } from 'react-spring';
import { Link } from 'react-router-dom';
import SVG from 'react-inlinesvg';

import style from './index.styl';

interface SideBarProps {
    visible: boolean;
    setSideBarVisibility: (visible: boolean) => void;
}

const Sidebar: React.FC<SideBarProps> = (props) => {
    const sidebarStart = '-100%';
    const sidebarEnd = '0%';

    const [{ x }, set] = useSpring(() => ({
        x: sidebarStart,
        from: { x: sidebarStart }
    }));

    set({ x: props.visible ? sidebarEnd : sidebarStart });

    return (
        <>
            <animated.div
                style={{ x }}
                className={
                    props.visible
                        ? `${style.sidebar} ${style.visible}`
                        : `${style.sidebar} ${style.hidden}`
                }
            >
                <div className={style.menuList}>
                    <div className={style.sidebarInner}>
                        <section>
                            <h4>
                                <Link to='/'>Library</Link>
                            </h4>
                            <ul>
                                <li>
                                    <Link to='/artists'>Artists</Link>
                                </li>
                                <li>
                                    <Link to='/albums'>Albums</Link>
                                </li>
                                <li>
                                    <Link to='/playlists'>Playlists</Link>
                                </li>
                                <li>
                                    <Link to='/favorites'>Favorites</Link>
                                </li>
                                <li>
                                    <Link to='/random'>Random</Link>
                                </li>
                            </ul>
                        </section>
                        <section>
                            <h4>Control</h4>
                            <ul>
                                <li>
                                    <Link to='/democratic'>Democratic</Link>
                                </li>
                                <li>
                                    <Link to='/radio'>Radio Stations</Link>
                                </li>
                                <li>
                                    <Link to='/localplay'>Localplay</Link>
                                </li>
                            </ul>
                        </section>
                        <section>
                            <h4>Insights</h4>
                            <ul>
                                <li>
                                    <Link to='/recent'>Recent</Link>
                                </li>
                                <li>
                                    <Link to='/newest'>Newest</Link>
                                </li>
                                <li>
                                    <Link to='/popular'>Popular</Link>
                                </li>
                                <li>
                                    <Link to='/top'>Top Rated</Link>
                                </li>
                                <li>
                                    <Link to='/tagcloud'>Tag Cloud</Link>
                                </li>
                                <li>
                                    <Link to='/statistics'>Statistics</Link>
                                </li>
                            </ul>
                        </section>
                    </div>
                </div>
                <div className={style.menuSettings}>
                    <h4>Settings</h4>
                    <ul>
                        <li>
                            <Link to='/user'>
                                User profile
                                <SVG
                                    src={require('~images/icons/svg/more-options-hori.svg')}
                                    title='User options'
                                    role='button'
                                    onClick={() => {
                                        // TODO: open more options menu;
                                    }}
                                    className='icon icon-inline'
                                />
                            </Link>
                        </li>
                        <li>
                            <Link to='/settings'>Ampache settings</Link>
                        </li>
                    </ul>
                </div>
            </animated.div>
            <div
                className={style.backdrop}
                onClick={() => props.setSideBarVisibility(false)}
            />
        </>
    );
};

export default Sidebar;
