/* global events, ControlsHandler, NeonShapes */

//UberViz AudioHandler
//Handles Audio loading and Playback
//Handles Audio Analysis + publishes audio data
//Handles Tap BPM

var AudioHandler = (function() {

    //PUBLIC/////////////
    var audioContext = new AudioContext();
    var waveData = []; //waveform - from 0 - 1 . no sound is 0.5. Array [binCount]
    var levelsData = []; //levels of each frequecy - from 0 - 1 . no sound is 0. Array [levelsCount]
    var volume = 0; // averaged normalized level from 0 - 1
    var bpmTime = 0; // bpmTime ranges from 0 to 1. 0 = on beat. Based on tap bpm
    var ratedBPMTime = 550;//time between beats (msec) multiplied by BPMRate
    var levelHistory = []; //last 256 ave norm levels
    var bpmStart; //FIXME

    var BEAT_HOLD_TIME = 40; //num of frames to hold a beat
    var BEAT_DECAY_RATE = 0.98;
    var BEAT_MIN = 0.15; //level less than this is no beat

    //BPM STUFF
    var count = 0;
    var msecsFirst = 0;
    var msecsPrevious = 0;
    var msecsAvg = 633; //time between beats (msec)

    var timer;
    var gotBeat = false;

    var debugCtx;
    var debugW = 250;
    var debugH = 95;
    var chartW = 220;
    var chartH = 95;
    var aveBarWidth = 30;
    var bpmHeight = debugH - chartH;
    var debugSpacing = 2;
    var gradient;
    var gainNode;
    var filter1;
    var filter2;
    var filter3;
    var filter4;

    var freqByteData; //bars - bar data is from 0 - 256 in 512 bins. no sound is 0;
    var timeByteData; //waveform - waveform data is from 0-256 for 512 bins. no sound is 128.
    var levelsCount = 16; //should be factor of 512

    var binCount; //512
    var levelBins;

    var isPlayingAudio = false;

    var beatCutOff = 0;
    var beatTime = 0;

    var source;
    var buffer;
    var audioBuffer;
    var dropArea;
    var processor;
    var analyser;

    var high = 0;


    function init() {

        //EVENT HANDLERS
        events.on("update", update);

        processor = audioContext.createScriptProcessor(2048 , 1 , 1 );

        analyser = audioContext.createAnalyser();
        analyser.smoothingTimeConstant = 0.3; //smooths out bar chart movement over time
        analyser.fftSize = 1024;
        analyser.connect(audioContext.destination);
        binCount = analyser.frequencyBinCount; // = 512

        initEqualizer();

        levelBins = Math.floor(binCount / levelsCount); //number of bins in each level

        freqByteData = new Uint8Array(binCount);
        timeByteData = new Uint8Array(binCount);

        var length = 256;
        for(var i = 0; i < length; i++) {
            levelHistory.push(0);
        }

        //INIT DEBUG DRAW
        var canvas = document.getElementById("audioDebug");
        debugCtx = canvas.getContext("2d");
        debugCtx.width = debugW;
        debugCtx.height = debugH;
        debugCtx.fillStyle = "rgb(40, 40, 40)";
        debugCtx.lineWidth=2;
        debugCtx.strokeStyle = "rgb(255, 255, 255)";
        $("#audioDebugCtx").hide();

        gradient = debugCtx.createLinearGradient(0,0,0,256);
        gradient.addColorStop(1,"#330000");
        gradient.addColorStop(0.75,"#aa0000");
        gradient.addColorStop(0.25,"#aaaa00");
        gradient.addColorStop(0,"#aaaaaa");

        //assume 120BPM
        msecsAvg = 640;
        timer = setInterval(onBMPBeat,msecsAvg);

    }

    function initEqualizer() {
        gainNode = audioContext.createGain();
        gainNode.gain.value = 1;

        filter1 = audioContext.createBiquadFilter();
        filter1.type = 5;
        filter1.gain.value = null;
        filter1.Q.value = 1;  // Change Filter type to test
        filter1.frequency.value = 80;  // Change frequency to test

        filter2 = audioContext.createBiquadFilter();
        filter2.type = 5;
        filter2.gain.value = 0;
        filter2.Q.value = 1;  // Change Filter type to test
        filter2.frequency.value = 240;  // Change frequency to test

        filter3 = audioContext.createBiquadFilter();
        filter3.type = 5;
        filter3.gain.value = 0;
        filter3.Q.value = 1;  // Change Filter type to test
        filter3.frequency.value = 750;  // Change frequency to test

        filter4 = audioContext.createBiquadFilter();
        filter4.type = 5;
        filter4.gain.value = 0;
        filter4.Q.value = 1;  // Change Filter type to test
        filter4.frequency.value = 2200;  // Change frequency to test

        filter5 = audioContext.createBiquadFilter();
        filter5.type = 5;
        filter5.gain.value = 0;
        filter5.Q.value = 1;  // Change Filter type to test
        filter5.frequency.value = 6000;  // Change frequency to test

        var sliderParams80Hz = {
            "orientation": "vertical",
            "range": "min",
            "min": -30,
            "max": 30,
            "animate": true,
            "step": 0.01,
            "slide"(event, ui) {
                filter1.gain.value = ui.value;

             },
            "stop"(event, ui) {
                console.log(filter1.gain.value);
            }
        };
        $("#filter80Hz").slider(sliderParams80Hz);

        var sliderParams240Hz = {
            "orientation": "vertical",
            "range": "min",
            "min": -30,
            "max": 30,
            "animate": true,
            "step": 0.01,
            "slide"(event, ui) {
                filter2.gain.value = ui.value;

             },
            "stop"(event, ui) {
                console.log(filter2.gain.value);
            }
        };
        $("#filter240Hz").slider(sliderParams240Hz);

        var sliderParams750Hz = {
            "orientation": "vertical",
            "range": "min",
            "min": -30,
            "max": 30,
            "animate": true,
            "step": 0.01,
            "slide"(event, ui) {
                filter3.gain.value = ui.value;

             },
            "stop"(event, ui) {
                console.log(filter3.gain.value);
            }
        };
        $("#filter750Hz").slider(sliderParams750Hz);

        var sliderParams2200Hz = {
            "orientation": "vertical",
            "range": "min",
            "min": -30,
            "max": 30,
            "animate": true,
            "step": 0.01,
            "slide"(event, ui) {
                filter4.gain.value = ui.value;

             },
            "stop"(event, ui) {
                console.log(filter4.gain.value);
            }
        };
        $("#filter2200Hz").slider(sliderParams2200Hz);

        var sliderParams6000Hz = {
            "orientation": "vertical",
            "range": "min",
            "min": -30,
            "max": 30,
            "animate": true,
            "step": 0.01,
            "slide"(event, ui) {
                filter5.gain.value = ui.value;

             },
            "stop"(event, ui) {
                console.log(filter5.gain.value);
            }
        };
        $("#filter6000Hz").slider(sliderParams6000Hz);
    }

    function initSound(){
        source = audioContext.createBufferSource();
        source.connect(analyser);
    }

    function startSound() {
        source.buffer = audioBuffer;
        source.loop = true;
        source.start();
        isPlayingAudio = true;
        //startViz();
    }

    function loadMediaSource(mediaElement) {
        if (typeof mediaElement !== "undefined") {
            if (mediaSource === null) {
                mediaSource = audioContext.createMediaElementSource(mediaElement);
            }
            source = mediaSource;
            source.connect(analyser);
            analyser.connect(gainNode);
            gainNode.connect(filter1);
            filter1.connect(filter2);
            filter2.connect(filter3);
            filter3.connect(filter4);
            filter4.connect(filter5);
            filter5.connect(audioContext.destination);
            isPlayingAudio = true;
        }
    }

    function stopSound(){
        isPlayingAudio = false;
        if (source) {
            source.disconnect();
        }
        debugCtx.clearRect(0, 0, debugW, debugH);
    }

    function onShowDebug(){
        if (ControlsHandler.audioParams.showDebug){
            $("#audioDebug").show();
        }else{
            $("#audioDebug").hide();
        }

    }

    function onBeat(){
        //console.log("BEAT");
        // TweenLite.to(this, 1, {debugLum:, ease:Power2.easeOut});
        // TweenMax.to(this, 1, css:{ color: "FFFFFF" } );

        //experimental combined beat + bpm mode
        gotBeat = true;

        if (ControlsHandler.audioParams.bpmMode) {
            return;
        }

        events.emit("onBeat");
    }

    function onBMPBeat(){
        //console.log("onBMPBeat");
        bpmStart = new Date().getTime();

        if (!ControlsHandler.audioParams.bpmMode) {
            return;
        }

        //only fire bpm beat if there was an on onBeat in last timeframe
        //experimental combined beat + bpm mode
        //if (gotBeat){
            NeonShapes.onBPMBeat();
            //GoldShapes.onBPMBeat();
            gotBeat = false;
        //}

    }

    //called every frame
    //update published viz data
    function update(){


        //console.log("audio.update");

        if (!isPlayingAudio) {
            return;
        }

        //GET DATA
        analyser.getByteFrequencyData(freqByteData); //<-- bar chart
        analyser.getByteTimeDomainData(timeByteData); // <-- waveform

        //normalize waveform data
        for(var i = 0; i < binCount; i++) {
            waveData[i] = ((timeByteData[i] - 128) /128 )* ControlsHandler.audioParams.volSens;
        }
        //TODO - cap levels at 1 and -1 ?

        //normalize levelsData from freqByteData
        for(var i = 0; i < levelsCount; i++) {
            var sum = 0;
            for(var j = 0; j < levelBins; j++) {
                sum += freqByteData[(i * levelBins) + j];
            }
            levelsData[i] = sum / levelBins/256 * ControlsHandler.audioParams.volSens; //freqData maxs at 256

            //adjust for the fact that lower levels are percieved more quietly
            //make lower levels smaller
            //levelsData[i] *=  1 + (i/levelsCount)/2; //??????
        }
        //TODO - cap levels at 1?

        //GET AVG LEVEL
        var sum = 0;
        for(var j = 0; j < levelsCount; j++) {
            sum += levelsData[j];
        }

        volume = sum / levelsCount;

        // high = Math.max(high,level);
        levelHistory.push(volume);
        levelHistory.shift(1);

        //BEAT DETECTION
        if (volume  > beatCutOff && volume > BEAT_MIN){
            onBeat();
            beatCutOff = volume *1.1;
            beatTime = 0;
        }else{
            if (beatTime <= ControlsHandler.audioParams.beatHoldTime){
                beatTime ++;
            }else{
                beatCutOff *= ControlsHandler.audioParams.beatDecayRate;
                beatCutOff = Math.max(beatCutOff,BEAT_MIN);
            }
        }

        bpmTime = (new Date().getTime() - bpmStart)/msecsAvg;
        //trace(bpmStart);

        if (ControlsHandler.audioParams.showDebug) {
            debugDraw();
        }
    }

    function debugDraw(){

        debugCtx.clearRect(0, 0, debugW, debugH);
        //draw chart bkgnd
        debugCtx.fillStyle = "#000";
        debugCtx.fillRect(0,0,debugW,debugH);

        //DRAW BAR CHART
        // Break the samples up into bars
        var barWidth = chartW / levelsCount;
        debugCtx.fillStyle=gradient;
        for(var i = 0; i < levelsCount; i++) {
            debugCtx.fillRect(i * barWidth, chartH, barWidth - debugSpacing, -levelsData[i]*chartH);
        }

        //DRAW AVE LEVEL + BEAT COLOR
        if (beatTime < 6){
            debugCtx.fillStyle="#FFF";
        }
        debugCtx.fillRect(chartW, chartH, aveBarWidth, -volume*chartH);

        //DRAW CUT OFF
        debugCtx.beginPath();
        debugCtx.moveTo(chartW , chartH - beatCutOff*chartH);
        debugCtx.lineTo(chartW + aveBarWidth, chartH - beatCutOff*chartH);
        debugCtx.stroke();

        //DRAW WAVEFORM
        debugCtx.beginPath();
        for(var i = 0; i < binCount; i++) {
            debugCtx.lineTo(i/binCount*chartW, waveData[i]*chartH/2 + chartH/2);
        }
        debugCtx.stroke();

        //DRAW BPM
        if (bpmHeight > 0) {
            var bpmMaxSize = bpmHeight;
            var size = bpmMaxSize - bpmTime*bpmMaxSize;
            debugCtx.fillStyle="#020";
            debugCtx.fillRect(0,chartH, bpmMaxSize, bpmMaxSize);
            debugCtx.fillStyle="#0F0";
            debugCtx.fillRect((bpmMaxSize - size)/2,chartH + (bpmMaxSize - size)/2, size, size);
        }
    }

    function onTap() {

        console.log("ontap");

        clearInterval(timer);

        timeSeconds = new Date();
        msecs = timeSeconds.getTime();

        //after 2 seconds, new tap counts as a new sequnce
        if ((msecs - msecsPrevious) > 2000){
            count = 0;
        }

        if (count === 0){
            console.log("First Beat");
            msecsFirst = msecs;
            count = 1;
        }else{
            bpmAvg = 60000 * count / (msecs - msecsFirst);
            msecsAvg = (msecs - msecsFirst)/count;
            count++;
            console.log("bpm: " + Math.round(bpmAvg * 100) / 100 + " , taps: " + count + " , msecs: " + msecsAvg);
            onBMPBeat();
            clearInterval(timer);
            timer = setInterval(onBMPBeat,msecsAvg);
        }
        msecsPrevious = msecs;
    }

    function onChangeBPMRate(){

        //change rate without losing current beat time

        //get ratedBPMTime from real bpm
        switch(ControlsHandler.audioParams.bpmRate)
        {
        case -3:
            ratedBPMTime = msecsAvg *8;
            break;
        case -2:
            ratedBPMTime = msecsAvg *4;
            break;
        case -1:
            ratedBPMTime = msecsAvg *2;
            break;
        case 0:
            ratedBPMTime = msecsAvg;
            break;
        case 1:
            ratedBPMTime = msecsAvg /2;
            break;
        case 2:
            ratedBPMTime = msecsAvg /4;
            break;
        case 3:
            ratedBPMTime = msecsAvg /8;
            break;
        case 4:
            ratedBPMTime = msecsAvg /16;
            break;
        }

        //console.log("ratedBPMTime: " + ratedBPMTime);


        //get distance to next beat
        bpmTime = (new Date().getTime() - bpmStart)/msecsAvg;


        timeToNextBeat = ratedBPMTime - (new Date().getTime() - bpmStart);

        //set one-off timer for that
        clearInterval(timer);
        timer = setInterval(onFirstBPM,timeToNextBeat);


        //set timer for new beat rate


    }

    function onFirstBPM(){
        clearInterval(timer);
        timer = setInterval(onBMPBeat,ratedBPMTime);
    }

    // function toggleBPMMode(tog){
    //    console.log("PP");
    // }

    return {
        onShowDebug:onShowDebug,
        update:update,
        init:init,
        loadMediaSource:loadMediaSource,
        onTap:onTap,
        onChangeBPMRate:onChangeBPMRate,
        getLevelsData() { return levelsData;},
        getVolume() { return volume;},
        getBPMTime() { return bpmTime;}

    };

}());
