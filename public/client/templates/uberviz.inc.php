<?php

// uberviz.inc.php
// Lightweight Web Audio AnalyserNode + canvas visualizer overlay.
// (Replaces the former UberViz/Three.js WebGL visualizer.)
?>
<div id="visualizer" style="visibility: hidden;">
    <canvas id="viz-canvas"></canvas>
</div>
<div id="equalizer" style="visibility: hidden;">
    <?php foreach (['80', '240', '750', '2.2k', '6k'] as $eqIndex => $eqLabel) { ?>
    <div class="eq-band">
        <input type="range" min="-20" max="20" step="1" value="0" oninput="setEqBand(<?php echo $eqIndex; ?>, this.value)" title="<?php echo $eqLabel; ?> Hz">
        <span><?php echo $eqLabel; ?></span>
    </div>
    <?php } ?>
</div>
