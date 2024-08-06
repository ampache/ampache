/* global events, THREE, VizHandler, ATUtil, ControlsHandler */

//UberViz FXHandler
//Handles Post-Proc Shaders

var FXHandler = (function() {

    var shaderTime = 0;
    var screenW = 800;
    var screenH = 600;
    var blurriness = 3;

    function init(){

        //EVENT HANDLERS
        events.on("update", update);
        events.on("onBeat", onBeat);

        // POST PROCESSING
        //common render target params
        var renderTargetParameters = { minFilter: THREE.LinearFilter, magFilter: THREE.LinearFilter, format: THREE.RGBFormat, stencilBufer: false };

        //RENDER COMP - BASE LAYER
        //renderComposer
        renderTarget = new THREE.WebGLRenderTarget( screenW, screenH, renderTargetParameters );
        renderComposer = new THREE.EffectComposer( VizHandler.getRenderer(),renderTarget);
        renderPass = new THREE.RenderPass( VizHandler.getScene(), VizHandler.getCamera() );
        copyPass = new THREE.ShaderPass( THREE.CopyShader );
        bloomPass = new THREE.BloomPass(3,12,2.0,512  );
        renderComposer.addPass( renderPass );
        renderComposer.addPass( bloomPass );
        renderComposer.addPass( copyPass );

        //GLOW COMP - ADDIITVELY BLENDED LAYER
        hblurPass = new THREE.ShaderPass( THREE.HorizontalBlurShader );
        vblurPass = new THREE.ShaderPass( THREE.VerticalBlurShader );
        hblurPass.uniforms[ "h" ].value = blurriness/screenW;
        vblurPass.uniforms[ "v" ].value = blurriness/screenH;
        copyPass = new THREE.ShaderPass( THREE.CopyShader );
        renderTarget2 = new THREE.WebGLRenderTarget( screenW/4, screenH/4, renderTargetParameters ); //1/2 res for performance
        glowComposer = new THREE.EffectComposer( VizHandler.getRenderer(),renderTarget2);
        glowComposer.addPass( copyPass );
        glowComposer.addPass( renderPass );
        glowComposer.addPass( bloomPass );
        glowComposer.addPass( hblurPass );
        glowComposer.addPass( vblurPass );
        glowComposer.addPass( hblurPass );
        glowComposer.addPass( vblurPass );

        //BLEND COMP - COMBINE 1st 2 PASSES
        blendComposer = new THREE.EffectComposer( VizHandler.getRenderer() );
        blendPass = new THREE.ShaderPass( THREE.AdditiveBlendShader );
        blendPass.uniforms[ "tBase" ].value = renderComposer.renderTarget1;
        blendPass.uniforms[ "tAdd" ].value = glowComposer.renderTarget1;
        blendPass.uniforms[ "amount" ].value = 0;
        blendComposer.addPass( blendPass );


        //PASSES ON FINAL OUTPUT
        filmPass = new THREE.ShaderPass( THREE.FilmShader );
        filmPass.uniforms[ "grayscale" ].value = 0;
        filmPass.uniforms[ "sIntensity" ].value = 0.8;
        filmPass.uniforms[ "sCount" ].value = 600;

        badTVPass = new THREE.ShaderPass( THREE.BadTVShader );
        badTVPass.uniforms[ "rollSpeed" ].value = 0;
        badTVPass.uniforms[ "distortion" ].value = 0;
        badTVPass.uniforms[ "distortion2" ].value = 0;

        mirrorPass = new THREE.ShaderPass( THREE.MirrorShader );
        mirrorPass.uniforms[ "side" ].value =2;

        rgbPass = new THREE.ShaderPass( THREE.RGBShiftShader );

        blendComposer.addPass( mirrorPass );
        blendComposer.addPass( badTVPass );
        blendComposer.addPass( rgbPass );
        blendComposer.addPass( filmPass );
        filmPass.renderToScreen = true;

    }

    function onBeat(){
        //beat detected
        badTVPass.uniforms[ "distortion" ].value = 4.0;
        badTVPass.uniforms[ "distortion2" ].value = 5.0;
        mirrorPass.uniforms[ "side" ].value = Math.floor(ATUtil.getRand(0,3));
        setTimeout(onBeatEnd,300);
    }

    function onBeatEnd(){
        badTVPass.uniforms[ "distortion" ].value = 0.0001;
        badTVPass.uniforms[ "distortion2" ].value = 0.0001;
    }

    function update( t ) {

        shaderTime += 0.1;
        filmPass.uniforms[ "time" ].value =  shaderTime;
        badTVPass.uniforms[ "time" ].value =  shaderTime;
        blendPass.uniforms[ "amount" ].value = ControlsHandler.fxParams.glow;

        renderComposer.render( 0.1 );
        glowComposer.render( 0.1 );
        blendComposer.render( 0.1 );

    }

    return {
        init:init,
        update:update,
        onBeat:onBeat
    };

}());