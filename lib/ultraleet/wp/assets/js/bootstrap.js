window.queueForRequire = [];

window._require = function (deps, callback) {
    window.queueForRequire.push({
        deps:     deps,
        callback: callback
    });
};

_require(['ulwp']);
