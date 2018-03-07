import React from 'react';
import ReactDOM from 'react-dom';

import { createStore, applyMiddleware } from 'redux';
import thunkMiddleware from 'redux-thunk'

import Main from './containers/Main/Main.js';
import reducers from './reducers';
import * as actions from './actions/chatActions.js';
import { AppContainer } from 'react-hot-loader'; //HMR

require ("../www/style/main.css");

// redux
//let store = createStore(chatApp);
// 调试模式
let store = createStore(reducers, window.devToolsExtension && window.devToolsExtension(), applyMiddleware(thunkMiddleware));

// root element
let rootElement = document.getElementById('app');

// Render the main app react component into the app div.
// For more details see: https://facebook.github.io/react/docs/top-level-api.html#react.render
const render = (Component) => {
    ReactDOM.render(
        <AppContainer>
            <Component store={ store }/>
        </AppContainer>,
        rootElement
    )
}

render(Main);

// if (module.hot) {
//   module.hot.accept('./containers/Main/Main.js', () => {
//     render(Main)
//     //window.location.reload()
//   });
// }