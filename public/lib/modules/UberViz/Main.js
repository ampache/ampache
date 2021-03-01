/* global Detector, AudioHandler, ControlsHandler, VizHandler, FXHandler */

//UberViz Main v0.1
//Handles HTML and wiring data
//Using Three v60

//GLOBAL
var events = new Events();
var simplexNoise = new SimplexNoise();


//MAIN RMP
var UberVizMain = (function() {

    var stats;
    var windowHalfX;
    var windowHalfY;

    function init() {

        console.log("ÜberViz v0.1.0");

        if(!Detector.webgl){
            Detector.addGetWebGLMessage();
        }

        //INIT DOCUMENT
        document.onselectstart = function() {
            return false;
        };

        document.addEventListener("mousemove", onDocumentMouseMove, false);
        document.addEventListener("mousedown", onDocumentMouseDown, false);
        document.addEventListener("mouseup", onDocumentMouseUp, false);
        document.addEventListener("drop", onDocumentDrop, false);
        document.addEventListener("dragover", onDocumentDragOver, false);
        window.addEventListener("resize", onResize, false);
        window.addEventListener("keydown", onKeyDown, false);
        window.addEventListener("keyup", onKeyUp, false);

        //STATS
        stats = new Stats();
        $("#controls").append(stats.domElement);
        stats.domElement.id = "stats";

        //INIT HANDLERS
        AudioHandler.init();
        ControlsHandler.init();
        VizHandler.init();
        FXHandler.init();

        onResize();


        if (ControlsHandler.vizParams.showControls){
            $("#controls").show();
        }

        update();

    }

    function update() {
        requestAnimationFrame(update);
        stats.update();
        events.emit("update");
    }

    function onDocumentDragOver(evt) {
        evt.stopPropagation();
        evt.preventDefault();
        return false;
    }

    //load dropped MP3
    function onDocumentDrop(evt) {
        evt.stopPropagation();
        evt.preventDefault();
        AudioHandler.onMP3Drop(evt);
    }

    function onKeyDown(event) {
        switch ( event.keyCode ) {
            case 32: /* space */
                AudioHandler.onTap();
                break;
            case 81: /* q */
                toggleControls();
                break;
        }
    }

    function onKeyUp(event) {
    }

    function onDocumentMouseDown(event) {
    }

    function onDocumentMouseUp(event) {
    }

    function onDocumentMouseMove(event) {
        // mouseX = (event.clientX - windowHalfX) / (windowHalfX);
        // mouseY = (event.clientY - windowHalfY) / (windowHalfY);
    }

    function onResize() {
        //windowHalfX = window.innerWidth / 2;
        //windowHalfY = window.innerHeight / 2;
        VizHandler.onResize();
    }

    function trace(text){
        $("#debugText").text(text);
    }

    function toggleControls(){
        ControlsHandler.vizParams.showControls = !ControlsHandler.vizParams.showControls;
        $("#controls").toggle();
        VizHandler.onResize();
    }

    return {
        init,
        trace
    };

}());