/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * Solves the proof-of-work challenge rendered by Ampache\Gui\Pow\PowWidgetView.
 *
 * The search runs in a worker so the page stays responsive, which is what lets a registration form
 * be filled in while the answer is being found: by the time the visitor reaches the submit button
 * the work is usually already done and they never waited for it.
 *
 * Where the page gives it a canvas, the wait is drawn with the web player's visualiser, fed by the
 * search instead of by an audio analyser. Digests supply the spectrum and every new best hash sets
 * off a burst, so the display slows down and brightens as the answer gets closer.
 */
(function () {
    'use strict';

    var widget = document.getElementById('pow-widget');
    if (!widget) {
        return;
    }

    var status = document.getElementById('pow-status');
    var nonceField = document.getElementById('pow_nonce');

    // Strings come translated from widget.phtml; xgettext never scans .js files.
    function text(name, fallback) {
        var value = widget.dataset['text' + name];

        return (typeof value === 'string' && value !== '') ? value : fallback;
    }
    var form = widget.closest('form');
    var autoSubmit = form !== null && form.dataset.powAutosubmit === '1';
    var submits = form
        ? form.querySelectorAll('input[type=submit], button[type=submit], button:not([type])')
        : [];

    function say(message) {
        if (status) {
            status.textContent = message;
        }
    }

    function setSubmitEnabled(enabled) {
        for (var index = 0; index < submits.length; index++) {
            submits[index].disabled = !enabled;
        }
    }

    if (typeof Worker === 'undefined') {
        say(text('Unsupported', 'Your browser is too old to pass this check.'));

        return;
    }

    setSubmitEnabled(false);

    /**
     * The web player's visualiser, driven by the search.
     *
     * `spectrum` holds bytes lifted straight out of recent digests, which is uniform noise, so an
     * envelope shapes it into something that reads as a spectrum. `beat` is set by a new best hash
     * and `progress` swells the whole thing as the search advances.
     */
    var visualizer = (function () {
        var canvas = document.getElementById('pow-canvas');
        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!canvas || !canvas.getContext || reduceMotion) {
            return null;
        }

        var BINS = 128;
        var spectrum = new Uint8Array(BINS);
        var writeHead = 0;
        var tick = 0;
        var beat = 0;
        var beatPrevious = 0;
        var progress = 0;
        var frame = null;
        var particles = [];
        var seeds = [];

        for (var seedIndex = 0; seedIndex < 160; seedIndex++) {
            seeds.push(Math.random());
        }

        /**
         * Digest words arrive as unsigned 32 bit integers; each contributes four bytes to the
         * rolling spectrum, which is what makes the bars shimmer with the actual search.
         */
        function feed(words, bestBits, ratio) {
            for (var index = 0; index < words.length; index++) {
                for (var shift = 24; shift >= 0; shift -= 8) {
                    spectrum[writeHead % BINS] = (words[index] >>> shift) & 255;
                    writeHead++;
                }
            }

            if (bestBits > beatPrevious) {
                // Each extra zero bit is twice as rare as the last, so later bursts are both scarcer
                // and stronger, and the display visibly tightens as the answer approaches.
                beat = Math.min(1, 0.45 + (bestBits - beatPrevious) * 0.2);
                beatPrevious = bestBits;
            }

            progress = Math.max(progress, Math.min(1, ratio));
        }

        function burst(context, centreX, centreY, radius, strength, hueShift) {
            var count = 6 + Math.floor(strength * 14);

            for (var index = 0; index < count; index++) {
                var angle = (index / count) * Math.PI * 2 + Math.random() * 0.4;
                var speed = (2 + Math.random() * 3) * (1 + strength);

                particles.push({
                    x: centreX + Math.cos(angle) * radius,
                    y: centreY + Math.sin(angle) * radius,
                    vx: Math.cos(angle) * speed,
                    vy: Math.sin(angle) * speed,
                    life: 1,
                    hue: Math.round(150 + Math.random() * 160 + hueShift) % 360
                });
            }
        }

        function draw() {
            var context = canvas.getContext('2d');
            var ratio = window.devicePixelRatio || 1;
            var width = canvas.clientWidth || 300;
            var height = canvas.clientHeight || 300;

            if (canvas.width !== Math.round(width * ratio)) {
                canvas.width = Math.round(width * ratio);
                canvas.height = Math.round(height * ratio);
            }

            context.setTransform(ratio, 0, 0, ratio, 0, 0);
            context.clearRect(0, 0, width, height);

            tick += 0.004;

            // Idle motion keeps the shape alive between worker messages, which arrive far less often
            // than frames do.
            var energy = 0.35 + progress * 0.45 + beat * 0.4;
            var loudness = 0;
            var values = new Float32Array(BINS);

            for (var bin = 0; bin < BINS; bin++) {
                var falloff = Math.pow(1 - bin / BINS, 0.7);
                var wave = 0.6 + 0.4 * Math.sin(bin * 0.35 + tick * 55);
                var value = (spectrum[bin] / 255) * falloff * wave * energy;

                values[bin] = value;
                loudness += value;
            }

            var average = loudness / BINS;
            var bass = Math.min(1, 0.2 + progress * 0.3 + beat * 0.8);

            var centreX = width / 2;
            var centreY = height / 2;
            var minDimension = Math.min(width, height);
            var baseRadius = minDimension * 0.11 * (1 + average * 0.9);
            var maxLength = minDimension * 0.46;
            var spokes = 120;
            var hueShift = (tick * 40) % 360;

            context.lineCap = 'round';
            context.globalCompositeOperation = 'lighter';

            // Soft radial core glow that swells with the bass.
            var coreRadius = baseRadius * (2.4 + bass * 2.2);
            var core = context.createRadialGradient(centreX, centreY, 0, centreX, centreY, coreRadius);
            core.addColorStop(0, 'hsla(' + Math.round(190 + hueShift) % 360 + ', 95%, 65%, ' + (0.35 + bass * 0.5) + ')');
            core.addColorStop(1, 'hsla(' + Math.round(190 + hueShift) % 360 + ', 95%, 55%, 0)');
            context.fillStyle = core;
            context.beginPath();
            context.arc(centreX, centreY, coreRadius, 0, Math.PI * 2);
            context.fill();

            context.shadowBlur = 8 + average * 22;

            for (var spoke = 0; spoke < spokes; spoke++) {
                var reading = values[Math.floor(spoke * BINS / spokes)];
                var seed = seeds[spoke % seeds.length];
                var angle = (spoke / spokes) * Math.PI * 2 + tick + seed * 0.4;
                var length = baseRadius + reading * maxLength * (0.5 + seed * 0.9);
                var hue = Math.round(150 + seed * 130 + reading * 50 + hueShift) % 360;
                var colour = 'hsl(' + hue + ', 90%, ' + Math.round(50 + reading * 30) + '%)';
                var tipX = centreX + Math.cos(angle) * length;
                var tipY = centreY + Math.sin(angle) * length;

                context.strokeStyle = colour;
                context.shadowColor = colour;
                context.lineWidth = 3 + reading * 9;
                context.beginPath();
                context.moveTo(centreX + Math.cos(angle) * baseRadius, centreY + Math.sin(angle) * baseRadius);
                context.lineTo(tipX, tipY);
                context.stroke();

                if (reading > 0.35) {
                    context.fillStyle = 'hsl(' + hue + ', 95%, 75%)';
                    context.beginPath();
                    context.arc(tipX, tipY, 2 + reading * 4, 0, Math.PI * 2);
                    context.fill();
                }
            }

            context.shadowBlur = 0;

            // Reactive ring pulsing with loudness.
            context.beginPath();
            context.arc(centreX, centreY, baseRadius * (1.15 + average * 0.7), 0, Math.PI * 2);
            context.strokeStyle = 'hsla(' + Math.round(190 + hueShift) % 360 + ', 90%, 70%, ' + (0.3 + average * 0.5) + ')';
            context.lineWidth = 2 + average * 6;
            context.stroke();

            if (beat > 0.35) {
                burst(context, centreX, centreY, baseRadius, beat, hueShift);
            }

            beat *= 0.9;

            for (var index = particles.length - 1; index >= 0; index--) {
                var particle = particles[index];

                particle.x += particle.vx;
                particle.y += particle.vy;
                particle.vx *= 0.96;
                particle.vy *= 0.96;
                particle.life -= 0.02;

                if (particle.life <= 0) {
                    particles.splice(index, 1);
                    continue;
                }

                context.fillStyle = 'hsla(' + particle.hue + ', 95%, 70%, ' + particle.life + ')';
                context.beginPath();
                context.arc(particle.x, particle.y, 1 + particle.life * 2.5, 0, Math.PI * 2);
                context.fill();
            }

            if (particles.length > 400) {
                particles.splice(0, particles.length - 400);
            }

            context.globalCompositeOperation = 'source-over';
            frame = window.requestAnimationFrame(draw);
        }

        return {
            start: function () {
                if (frame === null) {
                    frame = window.requestAnimationFrame(draw);
                }
            },
            feed: feed,
            /**
             * One last full-strength burst, then the animation is left to settle rather than being
             * cut off mid frame.
             */
            finish: function () {
                progress = 1;
                beat = 1;

                window.setTimeout(function () {
                    if (frame !== null) {
                        window.cancelAnimationFrame(frame);
                        frame = null;
                    }
                }, 1200);
            }
        };
    })();

    // Everything below this line runs inside the worker, hence the self-contained hash.
    var solver = function () {
        var K = new Uint32Array([
            0x428a2f98, 0x71374491, 0xb5c0fbcf, 0xe9b5dba5, 0x3956c25b, 0x59f111f1, 0x923f82a4, 0xab1c5ed5,
            0xd807aa98, 0x12835b01, 0x243185be, 0x550c7dc3, 0x72be5d74, 0x80deb1fe, 0x9bdc06a7, 0xc19bf174,
            0xe49b69c1, 0xefbe4786, 0x0fc19dc6, 0x240ca1cc, 0x2de92c6f, 0x4a7484aa, 0x5cb0a9dc, 0x76f988da,
            0x983e5152, 0xa831c66d, 0xb00327c8, 0xbf597fc7, 0xc6e00bf3, 0xd5a79147, 0x06ca6351, 0x14292967,
            0x27b70a85, 0x2e1b2138, 0x4d2c6dfc, 0x53380d13, 0x650a7354, 0x766a0abb, 0x81c2c92e, 0x92722c85,
            0xa2bfe8a1, 0xa81a664b, 0xc24b8b70, 0xc76c51a3, 0xd192e819, 0xd6990624, 0xf40e3585, 0x106aa070,
            0x19a4c116, 0x1e376c08, 0x2748774c, 0x34b0bcb5, 0x391c0cb3, 0x4ed8aa4a, 0x5b9cca4f, 0x682e6ff3,
            0x748f82ee, 0x78a5636f, 0x84c87814, 0x8cc70208, 0x90befffa, 0xa4506ceb, 0xbef9a3f7, 0xc67178f2
        ]);

        var W = new Uint32Array(64);

        /**
         * First 32 bits of sha256 over an ascii string of at most 55 bytes, which is a single block.
         * Only those bits are needed: the check is how many leading zero bits the digest has, and
         * PowService clamps what it asks for to 26.
         */
        function sha256First32(text) {
            W.fill(0);

            var length = text.length;
            var index;

            for (index = 0; index < length; index++) {
                W[index >> 2] |= text.charCodeAt(index) << (24 - (index % 4) * 8);
            }

            W[length >> 2] |= 0x80 << (24 - (length % 4) * 8);
            W[15] = length * 8;

            for (index = 16; index < 64; index++) {
                var w15 = W[index - 15];
                var w2 = W[index - 2];
                var s0 = ((w15 >>> 7) | (w15 << 25)) ^ ((w15 >>> 18) | (w15 << 14)) ^ (w15 >>> 3);
                var s1 = ((w2 >>> 17) | (w2 << 15)) ^ ((w2 >>> 19) | (w2 << 13)) ^ (w2 >>> 10);
                W[index] = (W[index - 16] + s0 + W[index - 7] + s1) | 0;
            }

            var h0 = 0x6a09e667;
            var a = h0;
            var b = 0xbb67ae85;
            var c = 0x3c6ef372;
            var d = 0xa54ff53a;
            var e = 0x510e527f;
            var f = 0x9b05688c;
            var g = 0x1f83d9ab;
            var h = 0x5be0cd19;

            for (index = 0; index < 64; index++) {
                var S1 = ((e >>> 6) | (e << 26)) ^ ((e >>> 11) | (e << 21)) ^ ((e >>> 25) | (e << 7));
                var ch = (e & f) ^ (~e & g);
                var t1 = (h + S1 + ch + K[index] + W[index]) | 0;
                var S0 = ((a >>> 2) | (a << 30)) ^ ((a >>> 13) | (a << 19)) ^ ((a >>> 22) | (a << 10));
                var maj = (a & b) ^ (a & c) ^ (b & c);
                var t2 = (S0 + maj) | 0;

                h = g;
                g = f;
                f = e;
                e = (d + t1) | 0;
                d = c;
                c = b;
                b = a;
                a = (t1 + t2) | 0;
            }

            return ((h0 + a) | 0) >>> 0;
        }

        self.onmessage = function (event) {
            var prefix = event.data.challenge + ':';
            var difficulty = event.data.difficulty;
            // `>>>` shifts modulo 32, so 32 is spelled out rather than wrapping to no work.
            var ceiling = difficulty >= 32 ? 0 : (0xffffffff >>> difficulty);
            var started = Date.now();
            var nonce = 0;
            var best = 0;

            for (;;) {
                // Small batches so the page has something to draw: at a few hundred thousand hashes
                // a second this reports roughly every other frame.
                var samples = [];

                for (var index = 0; index < 20000; index++) {
                    var digest = sha256First32(prefix + nonce);

                    if (digest <= ceiling) {
                        self.postMessage({done: true, nonce: String(nonce), elapsed: Date.now() - started});

                        return;
                    }

                    // Math.clz32 is exactly the measure the server checks the answer against, so the
                    // near misses the display reacts to are real progress rather than decoration.
                    var bits = Math.clz32(digest);
                    if (bits > best) {
                        best = bits;
                    }

                    if ((index & 1023) === 0) {
                        samples.push(digest);
                    }

                    nonce++;
                }

                self.postMessage({done: false, tried: nonce, best: best, samples: samples});
            }
        };
    };

    var blob = new Blob(['(' + solver.toString() + ')()'], {type: 'application/javascript'});
    var blobUrl = URL.createObjectURL(blob);
    var worker = new Worker(blobUrl);
    var difficulty = parseInt(widget.dataset.difficulty, 10);
    var expectedHashes = Math.pow(2, difficulty);

    if (visualizer) {
        visualizer.start();
    }

    worker.onmessage = function (event) {
        if (!event.data.done) {
            var ratio = event.data.tried / expectedHashes;

            if (visualizer) {
                visualizer.feed(event.data.samples, event.data.best, ratio);
            }

            // Memoryless search: this is an average solve, not remaining work. `%%` is unescaped
            // after `%d` so a literal percent cannot eat the placeholder.
            say(
                text('Progress', 'Checking your browser... %d%%')
                    .replace('%d', String(Math.min(99, Math.round(ratio * 100))))
                    .replace(/%%/g, '%')
            );

            return;
        }

        if (nonceField) {
            nonceField.value = event.data.nonce;
        }

        worker.terminate();
        URL.revokeObjectURL(blobUrl);

        say(text('Passed', 'Check passed.'));
        setSubmitEnabled(true);

        if (!autoSubmit || !form) {
            return;
        }

        if (!visualizer) {
            form.submit();

            return;
        }

        // Long enough for the closing burst to read as an ending, short enough that nobody waiting
        // on a download notices it.
        visualizer.finish();
        window.setTimeout(function () {
            form.submit();
        }, 600);
    };

    worker.onerror = function () {
        say(text('Error', 'The browser check failed to run. Please reload the page.'));
        setSubmitEnabled(true);
    };

    worker.postMessage({challenge: widget.dataset.challenge, difficulty: difficulty});
})();
