import * as Ajax from './ajax.js';
import * as Base from './base.js';
import * as Search from './search.js';
import * as Slideshow from './slideshow.js';
import * as Tools from './tools.js';

Object.assign(window, Ajax);
Object.assign(window, Base);
Object.assign(window, Tools);
Object.assign(window, Search);
Object.assign(window, Slideshow);