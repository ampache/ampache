/* global THREE */

/**
 * @author alteredq / http://alteredqualia.com/
 */

THREE.ShaderPass = function ( shader, textureID ) {

    this.textureID = ( typeof textureID !== "undefined" ) ? textureID : "tDiffuse";

    this.uniforms = THREE.UniformsUtils.clone( shader.uniforms );

    this.material = new THREE.ShaderMaterial( {

        uniforms: this.uniforms,
        vertexShader: shader.vertexShader,
        fragmentShader: shader.fragmentShader

    } );

    this.renderToScreen = false;

    this.enabled = true;
    this.needsSwap = true;
    this.clear = false;

};

THREE.ShaderPass.prototype = {

    render( renderer, writeBuffer, readBuffer, delta ) {

        if ( this.uniforms[ this.textureID ] ) {

            this.uniforms[ this.textureID ].value = readBuffer;

        }

        THREE.EffectComposer.quad.material = this.material;

        if ( this.renderToScreen ) {

            renderer.render( THREE.EffectComposer.scene, THREE.EffectComposer.camera );

        } else {

            renderer.render( THREE.EffectComposer.scene, THREE.EffectComposer.camera, writeBuffer, this.clear );

        }

    }

};
