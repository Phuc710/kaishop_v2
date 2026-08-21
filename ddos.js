#!/usr/bin/env node

/**
 * ╔══════════════════════════════════════════════════════════╗
 * ║   KaiShop Ultimate HTTP Stress Tester v3.0 (JS)        ║
 * ║   Multi-URL • Rotating UA • Live Graph • Full Report    ║
 * ╚══════════════════════════════════════════════════════════╝
 *
 * Usage:
 *   node stress.js <url> <total_requests> <concurrency> <timeout_sec>
 *
 * Examples:
 *   node stress.js https://kaishop.id.vn 2000 150 20
 *   node stress.js https://kaishop.id.vn/chatgpt/bypass 1000 80 15
 */

const https = require('https');
const http = require('http');
const url = require('url');
const { performance } = require('perf_hooks');

// ─── CLI Arguments ──────────────────────────────────────────
const args = process.argv.slice(2);
const baseUrl = args[0] || 'https://kaishop.id.vn';
const totalRequests = parseInt(args[1]) || 1000;
const concurrency = parseInt(args[2]) || 100;
const timeoutSec = parseInt(args[3]) || 20;

// ─── Multi-URL targets (randomly picked each request) ─────
const urlTargets = [
    baseUrl,
    baseUrl + '/',
    baseUrl + '/login',
    baseUrl + '/register',
    baseUrl + '/api/v1/status',
];

// ─── Rotating User-Agents Pool ─────────────────────────────
const userAgents = [
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Safari/605.1.15',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
    'Mozilla/5.0 (iPhone; CPU iPhone OS 17_3 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.3 Mobile/15E148 Safari/604.1',
    'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Mobile Safari/537.36',
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
    'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
    'curl/8.5.0',
    'python-requests/2.31.0',
];

const acceptLangs = [
    'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7',
    'en-US,en;q=0.9',
    'en-GB,en;q=0.8,fr;q=0.7',
    'zh-CN,zh;q=0.9,en;q=0.8',
    'ja-JP,ja;q=0.9,en;q=0.8',
];

// ─── Helper: random item from array ──────────────────────
const random = arr => arr[Math.floor(Math.random() * arr.length)];

// ─── HTTP request function (returns { status, timeMs, bytes, error }) ──
function doRequest(targetUrl, timeout) {
    return new Promise((resolve) => {
        const parsed = url.parse(targetUrl);
        const isHttps = parsed.protocol === 'https:';
        const agent = isHttps ? https : http;

        const options = {
            hostname: parsed.hostname,
            port: parsed.port || (isHttps ? 443 : 80),
            path: parsed.path || '/',
            method: 'GET',
            headers: {
                'User-Agent': random(userAgents),
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language': random(acceptLangs),
                'Accept-Encoding': 'gzip, deflate, br',
                'Connection': 'keep-alive',
                'Cache-Control': 'no-cache',
                'Pragma': 'no-cache',
                'Upgrade-Insecure-Requests': '1',
                'Sec-Fetch-Dest': 'document',
                'Sec-Fetch-Mode': 'navigate',
                'Sec-Fetch-Site': 'none',
                'X-Benchmark-Tool': 'KaiStress/3.0-JS',
            },
            timeout: timeout * 1000,
        };

        const start = performance.now();
        const req = agent.request(options, (res) => {
            let body = [];
            res.on('data', chunk => body.push(chunk));
            res.on('end', () => {
                const elapsed = performance.now() - start;
                const bytes = Buffer.concat(body).length;
                resolve({
                    status: res.statusCode,
                    timeMs: elapsed,
                    bytes: bytes,
                    error: null,
                });
            });
        });

        req.on('error', (err) => {
            const elapsed = performance.now() - start;
            resolve({
                status: 0,
                timeMs: elapsed,
                bytes: 0,
                error: err.message,
            });
        });

        req.on('timeout', () => {
            req.destroy();
            resolve({
                status: 0,
                timeMs: timeout * 1000,
                bytes: 0,
                error: 'Timeout',
            });
        });

        req.end();
    });
}

// ─── Main Stress Test ──────────────────────────────────────
async function runStress() {
    const startTime = performance.now();
    let completed = 0;
    let queued = 0;
    const statusCodes = {};
    const latencies = [];
    let totalBytes = 0;
    const errors = {};
    let timeoutCount = 0;

    // Live RPS window (last 5 seconds)
    const rpsWindow = {};
    const windowSize = 5;
    let peakRps = 0;
    let lastPrintTime = startTime;

    // Progress bar state
    const barLength = 30;

    // Worker pool: we'll use a simple queue with concurrency limit
    const workers = [];
    let resolveAll = null;
    const allDone = new Promise(resolve => { resolveAll = resolve; });

    // Function to launch a worker
    async function worker() {
        while (queued < totalRequests) {
            const idx = queued++;
            const target = random(urlTargets);
            const result = await doRequest(target, timeoutSec);
            completed++;

            // Update stats
            const code = result.status;
            statusCodes[code] = (statusCodes[code] || 0) + 1;
            if (result.timeMs > 0) latencies.push(result.timeMs);
            totalBytes += result.bytes;
            if (result.error) {
                const errKey = result.error.includes('Timeout') ? 'Timeout' : result.error;
                errors[errKey] = (errors[errKey] || 0) + 1;
                if (errKey === 'Timeout') timeoutCount++;
            }

            // Update RPS window
            const nowSec = Math.floor(performance.now() / 1000);
            rpsWindow[nowSec] = (rpsWindow[nowSec] || 0) + 1;
            for (const ts of Object.keys(rpsWindow)) {
                if (parseInt(ts) < nowSec - windowSize) delete rpsWindow[ts];
            }
            const windowRps = Object.values(rpsWindow).reduce((a,b) => a+b, 0) / 
                              Math.min(windowSize, Math.max(1, nowSec - Math.floor(startTime/1000) + 1));
            if (windowRps > peakRps) peakRps = windowRps;

            // Print progress every 2 seconds or at completion
            const now = performance.now();
            if (now - lastPrintTime >= 2000 || completed === totalRequests) {
                lastPrintTime = now;
                const elapsed = (now - startTime) / 1000;
                const pct = completed / totalRequests;
                const avgRps = elapsed > 0 ? completed / elapsed : 0;
                const eta = (avgRps > 0 && completed < totalRequests) 
                            ? Math.round((totalRequests - completed) / avgRps) 
                            : 0;
                const ok200 = statusCodes[200] || 0;
                const successPct = completed > 0 ? (ok200 / completed) * 100 : 0;
                const filled = Math.round(pct * barLength);
                const bar = '[' + '█'.repeat(filled) + '░'.repeat(barLength - filled) + ']';

                process.stdout.write(
                    `  ${bar} ${Math.round(pct*100)}% | ${completed}/${totalRequests} reqs | 200OK: ${ok200} (${successPct.toFixed(1)}%) | RPS: ${avgRps.toFixed(1)} | Timeout: ${timeoutCount} | ETA: ${eta}s\n`
                );
            }

            if (completed === totalRequests) {
                resolveAll();
                break;
            }
        }
    }

    // Start workers
    for (let i = 0; i < Math.min(concurrency, totalRequests); i++) {
        workers.push(worker());
    }

    await allDone;
    await Promise.all(workers); // wait for all to finish

    const elapsed = (performance.now() - startTime) / 1000;

    // ─── Final Report ──────────────────────────────────────────
    const ok200 = statusCodes[200] || 0;
    const failed = completed - ok200;
    const totalMB = totalBytes / 1048576;
    const avgRps = elapsed > 0 ? completed / elapsed : 0;
    const mbPerSec = elapsed > 0 ? totalMB / elapsed : 0;

    console.log('\n' + '═'.repeat(70));
    console.log('  📋 FINAL BENCHMARK REPORT');
    console.log('═'.repeat(70));
    console.log(`  ${'Test Duration:'.padEnd(38)} ${elapsed.toFixed(2)} seconds (${(elapsed/60).toFixed(1)} min)`);
    console.log(`  ${'Total Requests Sent:'.padEnd(38)} ${completed.toLocaleString()}`);
    console.log(`  ${'Successful (HTTP 200 OK):'.padEnd(38)} ${ok200.toLocaleString()} (${((ok200/(completed||1))*100).toFixed(1)}%)`);
    console.log(`  ${'Client Timeouts:'.padEnd(38)} ${timeoutCount.toLocaleString()} (${((timeoutCount/(completed||1))*100).toFixed(1)}%)`);
    console.log(`  ${'Other Failures:'.padEnd(38)} ${Math.max(0, failed - timeoutCount)}`);
    console.log(`  ${'Peak Live RPS:'.padEnd(38)} ${peakRps.toFixed(2)} req/s`);
    console.log(`  ${'Average Throughput:'.padEnd(38)} ${avgRps.toFixed(2)} req/s`);
    console.log(`  ${'Total Data Transferred:'.padEnd(38)} ${totalMB.toFixed(2)} MB @ ${mbPerSec.toFixed(2)} MB/s`);

    console.log('\n' + '─'.repeat(70));
    console.log('  📊 HTTP Status Code Breakdown:');
    const sortedCodes = Object.entries(statusCodes).sort((a,b) => b[1] - a[1]);
    for (const [code, num] of sortedCodes) {
        const pctVal = (num / (completed||1)) * 100;
        const barW = Math.round(pctVal / 2);
        let icon = '❓';
        if (code == 200) icon = '✅';
        else if (code >= 300 && code < 400) icon = '↪️';
        else if (code == 403) icon = '🚫';
        else if (code == 429) icon = '⚡';
        else if (code >= 500) icon = '💥';
        else if (code == 0) icon = '⏱️';
        const desc = code == 200 ? 'OK' :
                     code == 301 ? 'Moved Permanently' :
                     code == 302 ? 'Found (Redirect)' :
                     code == 403 ? 'Forbidden (WAF/CF Block)' :
                     code == 404 ? 'Not Found' :
                     code == 429 ? 'Too Many Requests (AntiFlood)' :
                     code == 500 ? 'Internal Server Error 💣' :
                     code == 502 ? 'Bad Gateway (PHP-FPM Down)' :
                     code == 503 ? 'Service Unavailable' :
                     code == 504 ? 'Gateway Timeout' :
                     code == 0 ? 'Connection/Timeout Error' :
                     `HTTP ${code}`;
        console.log(`    ${icon}  [${code}] ${desc.padEnd(34)} ${num}  (${pctVal.toFixed(1)}%)  ${'▓'.repeat(barW)}`);
    }

    console.log('\n' + '─'.repeat(70));
    console.log('  ⏱️  Latency Distribution (successful responses only):');
    const validLat = latencies.filter(x => x < timeoutSec * 1000 * 0.99);
    if (validLat.length > 0) {
        const sortedLat = validLat.slice().sort((a,b) => a - b);
        const pct = (arr, p) => arr[Math.min(arr.length-1, Math.floor(arr.length * p))];
        console.log(`    ${'Min:'.padEnd(20)} ${sortedLat[0].toFixed(2)} ms`);
        console.log(`    ${'Average:'.padEnd(20)} ${(sortedLat.reduce((a,b)=>a+b,0)/sortedLat.length).toFixed(2)} ms`);
        console.log(`    ${'Median (P50):'.padEnd(20)} ${pct(sortedLat, 0.50).toFixed(2)} ms`);
        console.log(`    ${'P75:'.padEnd(20)} ${pct(sortedLat, 0.75).toFixed(2)} ms`);
        console.log(`    ${'P90:'.padEnd(20)} ${pct(sortedLat, 0.90).toFixed(2)} ms`);
        console.log(`    ${'P95:'.padEnd(20)} ${pct(sortedLat, 0.95).toFixed(2)} ms`);
        console.log(`    ${'P99:'.padEnd(20)} ${pct(sortedLat, 0.99).toFixed(2)} ms`);
        console.log(`    ${'Max:'.padEnd(20)} ${sortedLat[sortedLat.length-1].toFixed(2)} ms`);
    } else {
        console.log('    (No successful responses to measure latency)');
    }

    if (Object.keys(errors).length > 0) {
        console.log('\n' + '─'.repeat(70));
        console.log('  🔌 Network/Connection Errors:');
        const sortedErrors = Object.entries(errors).sort((a,b) => b[1] - a[1]);
        for (const [err, num] of sortedErrors) {
            console.log(`    - ${err.padEnd(40)}: ${num}`);
        }
    }

    console.log('\n' + '─'.repeat(70));
    console.log('  🧠 Server Capacity Assessment:');
    const crashFree = !(statusCodes[500] || statusCodes[502] || statusCodes[503]);
    const wafBlock = statusCodes[403] || 0;
    const rateLimit = statusCodes[429] || 0;
    console.log(`    ${'Server Crash (500/502/503):'.padEnd(40)} ${crashFree ? '✅ NONE — Server survived!' : '⚠️  Detected!'}`);
    console.log(`    ${'WAF/CF Firewall Blocks (403):'.padEnd(40)} ${wafBlock > 0 ? `🚫 ${wafBlock} requests blocked` : '✅ None triggered'}`);
    console.log(`    ${'AntiFlood Rate Limits (429):'.padEnd(40)} ${rateLimit > 0 ? `⚡ ${rateLimit} requests throttled` : '✅ None triggered'}`);
    console.log(`    ${'Sustainable Throughput Ceiling:'.padEnd(40)} ~${Math.round(avgRps)} req/s (${Math.round(avgRps*60)} req/min)`);

    console.log('\n' + '═'.repeat(70));
    console.log(`  ✅ Stress test complete. Report generated at ${new Date().toLocaleString()}`);
    console.log('═'.repeat(70) + '\n');
}

// ─── Run ──────────────────────────────────────────────────
runStress().catch(err => {
    console.error('Fatal error:', err);
    process.exit(1);
});



// Chạy:

// node stress.js https://kaishop.id.vn 5000 200 15
// Tham số: URL, tổng số request, số luồng đồng thời, timeout giây.

