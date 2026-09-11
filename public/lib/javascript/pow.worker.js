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
 * Worker for pow.js: searches for a nonce whose sha256 has enough leading zero bits.
 *
 * A separate file rather than a blob, so `worker-src` resolves against `script-src 'self'` under
 * the CSP shipped in docs/examples.
 */
(function () {
    'use strict';

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
})();
