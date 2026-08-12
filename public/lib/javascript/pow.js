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
 * The search itself lives in pow.worker.js so the page stays responsive, which is what lets a
 * registration form be filled in while the answer is being found: by the time the visitor reaches
 * the submit button the work is usually already done and they never waited for it.
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
    var sink = document.getElementById('pow-sink');
    var returnLink = document.getElementById('pow-return');
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

    var worker = new Worker(widget.dataset.worker);
    var difficulty = parseInt(widget.dataset.difficulty, 10);
    var expectedHashes = Math.pow(2, difficulty);

    if (visualizer) {
        visualizer.start();
    }

    /**
     * A token the endpoint echoes back as a cookie, so the page can tell when the delivery has
     * actually started. Only a UX signal: holding it authorises nothing.
     */
    function ackToken() {
        var bytes = new Uint8Array(16);
        var token = '';
        var index;

        if (window.crypto && window.crypto.getRandomValues) {
            window.crypto.getRandomValues(bytes);
        } else {
            for (index = 0; index < bytes.length; index++) {
                bytes[index] = Math.floor(Math.random() * 256);
            }
        }

        for (index = 0; index < bytes.length; index++) {
            token += (bytes[index] + 0x100).toString(16).slice(1);
        }

        return token;
    }

    function ackCookiePresent(token) {
        return document.cookie.split(';').some(function (entry) {
            return entry.trim() === 'pow_ack=' + token;
        });
    }

    /**
     * Hands the request back to its original endpoint and gets out of the way.
     *
     * The form targets a hidden iframe, so this document stays loaded: a zip is written in full
     * before its headers are sent, and unloading here would cancel the request. A download never
     * fires `load` on the frame, so a `load` means the endpoint answered with a page instead --
     * an error, or a fresh challenge -- and the visitor should be looking at it rather than at a
     * frame they cannot see.
     *
     * Returning waits for the acknowledgement cookie, which arrives with the download headers and at
     * no earlier moment. Before those headers the request is still a navigation the frame owns, and
     * leaving would cancel it; after them the browser owns the transfer and leaving is harmless.
     */
    function replay() {
        var returnUrl = form.dataset.powReturn;
        var acknowledges = form.dataset.powAck === '1';
        var ackField = document.getElementById('pow_ack');
        var token = ackToken();
        // Ampache's own recommended configs send `Referrer-Policy: no-referrer`, so on most installs
        // the server is handed nothing to build a return url from and falls back to the home page.
        // The tab still knows where the visitor came from, so history is the route and the url the
        // fallback, not the other way round.
        var canGoBack = window.history.length > 1;
        var timer = null;
        var poll = null;
        var leaving = false;

        function stop() {
            leaving = true;
            window.clearTimeout(timer);
            window.clearInterval(poll);
            // Spent: a token left behind would let the next visit return before its own delivery.
            document.cookie = 'pow_ack=; Path=/; Max-Age=0; SameSite=Lax';
        }

        /** The endpoint answered with a page rather than a file, so show it instead of going back. */
        function showResponse(url) {
            if (leaving || !url) {
                return;
            }

            stop();
            window.location.replace(url);
        }

        function goBack() {
            if (leaving) {
                return;
            }

            stop();

            if (canGoBack) {
                window.history.back();

                // A back() that lands unloads this page well inside the delay; one that finds
                // nothing to return to is silent, and this is what catches it.
                window.setTimeout(function () {
                    if (returnUrl) {
                        window.location.replace(returnUrl);
                    }
                }, 700);

                return;
            }

            if (returnUrl) {
                window.location.replace(returnUrl);
            }
        }

        if (sink) {
            sink.onload = function () {
                // Same origin, so the frame's own address is readable and there is nothing to
                // rebuild; the form action is only there in case a browser withholds it.
                var shown = form.action;

                try {
                    shown = sink.contentWindow.location.href || shown;
                } catch (error) {
                    shown = form.action;
                }

                showResponse(shown);
            };
        }

        if (ackField) {
            ackField.value = token;
        }

        form.submit();

        say(text('Started', 'Your download has started.'));

        if (returnLink) {
            returnLink.hidden = false;

            // The href is a working fallback on its own; this just routes the click through the
            // same history-first path the timer uses.
            var anchor = returnLink.querySelector('a');

            if (anchor) {
                anchor.addEventListener('click', function (event) {
                    event.preventDefault();
                    goBack();
                });
            }
        }

        // Where the endpoint acknowledges, the cookie is the signal and the timer only covers the
        // case where it never arrives. Where it does not, the timer is all there is, so it is short
        // enough not to strand the visitor: those endpoints stream, and their headers go out at once.
        if (acknowledges) {
            poll = window.setInterval(function () {
                if (ackCookiePresent(token)) {
                    goBack();
                }
            }, 250);
        }

        timer = window.setTimeout(goBack, acknowledges ? 30000 : 5000);
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

        say(text('Passed', 'Check passed.'));
        setSubmitEnabled(true);

        if (!autoSubmit || !form) {
            return;
        }

        if (!visualizer) {
            replay();

            return;
        }

        // Long enough for the closing burst to read as an ending, short enough that nobody waiting
        // on a download notices it.
        visualizer.finish();
        window.setTimeout(replay, 600);
    };

    worker.onerror = function () {
        say(text('Error', 'The browser check failed to run. Please reload the page.'));
        setSubmitEnabled(true);
    };

    worker.postMessage({challenge: widget.dataset.challenge, difficulty: difficulty});
})();
