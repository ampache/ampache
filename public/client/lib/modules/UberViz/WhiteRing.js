
/* global events, THREE, VizHandler, AudioHandler */

//WHITE RING VIZ
//white hollow flat shapes
// randomly generated

var WhiteRing = (function() {


    var groupHolder;
    var material;

    var drewNewShape = false;

    var shapes = [];

    var scl = 0;

    function init(){

        //init event listeners
        events.on("update", update);
        events.on("onBeat", onBeat);


        var radius = 1000;
        groupHolder = new THREE.Object3D();
        VizHandler.getVizHolder().add(groupHolder);

        material = new THREE.MeshBasicMaterial( {
            color: 0xFFFFFF,
            wireframe: false,
            //blending: THREE.AdditiveBlending,
            depthWrite:false,
            depthTest:false,
            transparent:true,
            opacity:1
        } );


        //empty square
        geometry = new THREE.RingGeometry( radius*.6,radius, 4,1, 0, Math.PI*2);
        mesh = new THREE.Mesh( geometry, material );
        groupHolder.add( mesh );
        shapes.push(mesh);


        //empty tri
        geometry = new THREE.RingGeometry( radius*.6,radius, 3,1, 0, Math.PI*2);
        mesh = new THREE.Mesh( geometry, material );
        groupHolder.add( mesh );
        shapes.push(mesh);

        //empty circ
        // geometry = new THREE.RingGeometry( radius*.6,radius, 24,1, 0, Math.PI*2);
        // mesh = new THREE.Mesh( geometry, material );
        // groupHolder.add( mesh );
        // shapes.push(mesh);

        shapesCount = shapes.length;

    }

    function showNewShape() {

        //random rotation
        groupHolder.rotation.z = Math.random()*Math.PI;

        //hide shapes
        for (var i = 0; i <= shapesCount-1;i++){
            shapes[i].rotation.y = Math.PI/2; //hiding by turning
        }

        //show a shape sometimes
        if (Math.random() < .5){
            var r = Math.floor(Math.random() * shapesCount);
            //console.log(r)
            shapes[r].rotation.y = Math.random()*Math.PI/4-Math.PI/8;
        }

    }

    function update() {
        groupHolder.rotation.z += 0.01;
        var gotoScale = AudioHandler.getVolume()*1.2 + .1;
        scl += (gotoScale - scl)/3;
        groupHolder.scale.x = groupHolder.scale.y = groupHolder.scale.z = scl;
    }

    function onBeat(){
        showNewShape();
    }

    return {
        init,
        update,
        onBeat
    };

}());