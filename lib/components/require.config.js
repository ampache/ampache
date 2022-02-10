var components = {
    "packages": [
        {
            "name": "tag-it",
            "main": "tag-it-built.js"
        },
        {
            "name": "jQuery-Knob",
            "main": "jQuery-Knob-built.js"
        },
        {
            "name": "jQuery-File-Upload",
            "main": "jQuery-File-Upload-built.js"
        },
        {
            "name": "bootstrap",
            "main": "bootstrap-built.js"
        },
        {
            "name": "jquery",
            "main": "jquery-built.js"
        },
        {
            "name": "jquery-ui",
            "main": "jquery-ui-built.js"
        },
        {
            "name": "jplayer",
            "main": "jplayer-built.js"
        },
        {
            "name": "jquery-qrcode",
            "main": "jquery-qrcode-built.js"
        },
        {
            "name": "js-cookie",
            "main": "js-cookie-built.js"
        },
        {
            "name": "responsive-elements",
            "main": "responsive-elements-built.js"
        },
        {
            "name": "jscroll",
            "main": "jscroll-built.js"
        },
        {
            "name": "prettyphoto",
            "main": "prettyphoto-built.js"
        },
        {
            "name": "jQuery-contextMenu",
            "main": "jQuery-contextMenu-built.js"
        },
        {
            "name": "jstree",
            "main": "jstree-built.js"
        },
        {
            "name": "datetimepicker",
            "main": "datetimepicker-built.js"
        }
    ],
    "shim": {
        "bootstrap": {
            "deps": [
                "jquery"
            ]
        },
        "jquery-ui": {
            "deps": [
                "jquery"
            ],
            "exports": "jQuery"
        },
        "jplayer": {
            "deps": [
                "jquery"
            ]
        }
    },
    "baseUrl": "components"
};
if (typeof require !== "undefined" && require.config) {
    require.config(components);
} else {
    var require = components;
}
if (typeof exports !== "undefined" && typeof module !== "undefined") {
    module.exports = components;
}