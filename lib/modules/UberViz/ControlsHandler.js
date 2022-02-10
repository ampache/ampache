/* global dat, AudioHandler */

//UberViz ControlsHandler
//Handles side menu controls

var ControlsHandler = (function() {

    var audioParams = {
        showDebug:true,
        volSens:1,
        beatHoldTime:40,
        beatDecayRate:0.97,
        bpmMode: false,
        bpmRate:0
    };

    var vizParams = {
        fullSize: true,
        showControls: false
        // useBars: false,
        // useGoldShapes: true,
        // useNebula:true,
        // useNeonShapes:true,
        // useStripes:true,
        // useTunnel:true,
        // useWaveform:true,
    };

    var fxParams = {
        glow: 1.0
    };

    function init(){

        //Init DAT GUI control panel
        gui = new dat.GUI({autoPlace: false });
        $("#settings").append(gui.domElement);

        var f2 = gui.addFolder("Audio");
        f2.add(audioParams, "volSens", 0, 10).step(0.1).name("Gain");
        f2.add(audioParams, "beatHoldTime", 0, 100).step(1).name("Beat Hold");
        f2.add(audioParams, "beatDecayRate", 0.9, 1).step(0.01).name("Beat Decay");
        // f2.add(audioParams, "bpmMode").listen();
        // f2.add(audioParams, "bpmRate", 0, 4).step(1).listen().onChange(AudioHandler.onChangeBPMRate);
        f2.open();

        //var f5 = gui.addFolder("Viz");
        var f5 = gui.addFolder("FX");
        f5.add(fxParams, "glow", 0, 4).step(0.1);
        //f5.open();
        AudioHandler.onShowDebug();

    }

    return {
        init,
        audioParams,
        fxParams,
        vizParams
    };
}());