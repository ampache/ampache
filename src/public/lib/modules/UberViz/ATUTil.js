/* global THREE */

ATUtil = {

    getRandVec3D(minVal, maxVal) {
        return new THREE.Vector3(ATUtil.getRand(minVal, maxVal), ATUtil.getRand(minVal, maxVal), ATUtil.getRand(minVal, maxVal));
    },
    getRand(minVal, maxVal) {
        return minVal + (Math.random() * (maxVal - minVal));
    },
    map(value, min1, max1, min2, max2) {
        return ATUtil.lerp(min2, max2, ATUtil.norm(value, min1, max1));
    },
    lerp(min, max, amt){
        return min + (max -min) * amt;
    },
    norm(value , min, max){
        return (value - min) / (max - min);
    }
};
