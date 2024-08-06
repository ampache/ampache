/* global events, VizHandler, THREE, AudioHandler, simplexNoise, ATUtil */

//GRAPHIC EQUALIZER BARS VIZ

var Bars = (function() {

    //Viz Template
    var groupHolder;
    var BAR_COUNT = 16;
    var vertDistance;
    var fillFactor= 0.8;
    var planeWidth = 2000;
    var segments = 10;

    function init(){

        //EVENT HANDLERS
        events.on("update", update);
        events.on("onBeat", onBeat);

        groupHolder = new THREE.Object3D();
        VizHandler.getVizHolder().add(groupHolder);
        groupHolder.position.z = 300;
        vertDistance = 1580 / BAR_COUNT;
        groupHolder.rotation.z = Math.PI/4;

        for ( var j = 0; j < BAR_COUNT; j ++ ) {

            var planeMat = new THREE.MeshBasicMaterial( {
                color: 0xEBFF33
                //side:THREE.DoubleSide //more complex shapes
            });
            planeMat.color.setHSL(j/BAR_COUNT, 1.0, 0.5);
            mesh = new THREE.Mesh( new THREE.PlaneGeometry( planeWidth, vertDistance,segments,segments), planeMat );
            mesh.position.y = vertDistance*j - (vertDistance*BAR_COUNT)/2;
            mesh.scale.y = (j+1)/BAR_COUNT*fillFactor;
            groupHolder.add( mesh );
        }
    }

    function displaceMesh(){

        //rejigger z disps
        var MAX_DISP =  Math.random() * 600;
        var rnd = Math.random();
        for ( var j = 0; j < BAR_COUNT; j ++ ) {
            var mesh = groupHolder.children[j];
            //randomly warp mesh
            for(var i=0; i < mesh.geometry.vertices.length; i++) {
                vertex = mesh.geometry.vertices[i];
                var disp = simplexNoise.noise(vertex.x / planeWidth*100 ,rnd) * MAX_DISP;
                vertex.z = disp;
            }
            mesh.geometry.verticesNeedUpdate = true;
        }
    }

    function update() {

        //slowly move up
        groupHolder.position.y = AudioHandler.getBPMTime() * vertDistance;

        //scale bars on levels
        for ( var j = 0; j < BAR_COUNT; j ++ ) {
            groupHolder.children[j].scale.y = AudioHandler.getLevelsData()[j] * AudioHandler.getLevelsData()[j] + 0.00001;
        }
    }

    function onBeat(){

        groupHolder.rotation.z = Math.PI/4 * Math.floor(ATUtil.getRand(0,4));

        //slight Y rotate
        groupHolder.rotation.y = ATUtil.getRand(-Math.PI/4,Math.PI/4);


        displaceMesh();

    }

    function onBPMBeat(){
    }

    return {
        init:init
    };

}());