/* global events, THREE, WhiteRing, Bars, ControlsHandler */

//UberViz VizHandler
//Handle 3D world
//Camera movement
//handle sub vizs

//RENDER AREA DIMS:
//SCREEN DIMS: 800 x 600
//CAM Z: 1000
//FAR CLIP Z: 3000
//TO FILL SCREEN AT Z 0: WIDTH 1840, HEIGHT: 1380


var VizHandler = (function() {

    var rendertime = 0; //constantly incrementing value public
    var camera, scene, renderer;
    var debugCube;
    var renderToggle = true;
    var vizHolder;

    var FIXED_SIZE_W = 800;
    var FIXED_SIZE_H = 600;

    function init(){

        //EVENT HANDLERS
        events.on("update", update);

        //RENDERER
        renderer = new THREE.WebGLRenderer({
            antialias: false
        });
        renderer.setSize(FIXED_SIZE_W,FIXED_SIZE_H);
        renderer.setClearColor ( 0x000000 );
        renderer.sortObjects = false;
        $("#viz").append(renderer.domElement);

        //3D SCENE
        camera = new THREE.PerspectiveCamera( 70, FIXED_SIZE_W / FIXED_SIZE_H, 1, 3000 );
        camera.position.z = 1000;
        scene = new THREE.Scene();
        scene.add(camera);

        scene.fog = new THREE.Fog( 0x000000, 2000, 3000 );

        //DEBUG
        // debugHolder =  new THREE.Object3D();
        // scene.add( debugHolder );
        // //debugHolder.visible = false;

        // //Boundary cube
        // var geometry = new THREE.CubeGeometry( 1000, 1000, 1000 );
        // var material = new THREE.MeshBasicMaterial( { color: 0xff0000, wireframe: true } );
        // var mesh = new THREE.Mesh( geometry, material );
        // debugHolder.add( mesh );

        // //Debug Cube
        // geometry = new THREE.CubeGeometry( 100, 100, 100 );
        // debugCube = new THREE.Mesh( geometry, material );
        // debugHolder.add( debugCube );

        // //Debug Plane
        // //covers visible area
        // mesh = new THREE.Mesh( new THREE.PlaneGeometry( 800*2.3, 600*2.3,5,5 ), new THREE.MeshBasicMaterial( { color: 0x00FF00, wireframe: true } )  );
        // debugHolder.add( mesh);


        //INIT VIZ
        vizHolder =  new THREE.Object3D();
        scene.add( vizHolder );

        //SET ACTIVE VIZ HERE
        activeViz = [Bars,WhiteRing];

        activeVizCount = activeViz.length;
        for ( var j = 0; j < activeVizCount; j ++ ) {
            activeViz[j].init();
        }

    }

    function update() {

        //render every other frame
        // renderToggle = !renderToggle;
        // if (!renderToggle) return;

        rendertime += 0.01;

        //DEBUG
        // if (level > 0 ) debugCube.scale.x = debugCube.scale.y = debugCube.scale.z = level*15;
        // debugCube.rotation.x += 0.01;
        // debugCube.rotation.y += 0.02;


        // renderer.render( scene, camera );

    }

    function onResize(){

        var renderW;
        var renderH;

        if (ControlsHandler.vizParams.fullSize){
            var renderW = window.innerWidth;
            var renderH = window.innerHeight - 7;

            if (ControlsHandler.vizParams.showControls){
                renderW -= 250;
            }

            $("#viz").css({top:0});

        }else{
            var renderW = FIXED_SIZE_W;
            var renderH = FIXED_SIZE_H;
            //vertically center viz output
            $("#viz").css({top:window.innerHeight/2 - FIXED_SIZE_H/2});
        }

        camera.aspect = renderW / renderH;
        camera.updateProjectionMatrix();
        renderer.setSize( renderW,renderH);
    }

    return {
        init: init,
        update: update,
        getVizHolder() { return vizHolder;},
        getCamera() { return camera;},
        getScene() { return scene;},
        getRenderer() { return renderer;},
        onResize: onResize
    };

}());
