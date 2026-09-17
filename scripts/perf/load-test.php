#!/usr/bin/env php
<?php

/**
 * Markedge repeatable load test (Phase 10 §37). Dependency-free (curl_multi).
 *
 *   php scripts/perf/load-test.php --base=http://127.0.0.1:8000 --concurrency=10 --requests=200 [--profile=browse|search|mixed]
 *
 * Reports throughput, latency percentiles, error rate and bytes per profile. Results from a single
 * machine running `php artisan serve` describe the application, not production capacity.
 */
$options = getopt('', ['base::', 'concurrency::', 'requests::', 'profile::']);
$base = rtrim($options['base'] ?? 'http://127.0.0.1:8000', '/');
$concurrency = max(1, (int) ($options['concurrency'] ?? 10));
$total = max($concurrency, (int) ($options['requests'] ?? 200));
$profile = $options['profile'] ?? 'mixed';

$profiles = [
    'browse' => ['/', '/services', '/services/software-development', '/products/lead-management-system', '/solutions/business-automation', '/industries/healthcare', '/insights'],
    'search' => ['/search?q=software', '/search?q=cloud+infrastructure', '/search?q=software&type=service', '/search?q=software&page=2', '/search?q=xyz-nothing'],
    'assets' => ['/sitemap.xml', '/robots.txt', '/health', '/health/ready'],
];
$profiles['mixed'] = array_merge($profiles['browse'], $profiles['browse'], $profiles['search'], $profiles['assets']);
$urls = $profiles[$profile] ?? $profiles['mixed'];

$multi = curl_multi_init();
$active = [];
$results = [];
$started = microtime(true);
$dispatched = 0;

$add = function () use (&$active, &$dispatched, $multi, $urls, $base): void {
    $url = $base.$urls[$dispatched % count($urls)];
    $handle = curl_init($url);
    curl_setopt_array($handle, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 30, CURLOPT_ENCODING => '', CURLOPT_USERAGENT => 'markedge-loadtest']);
    curl_multi_add_handle($multi, $handle);
    $active[(int) $handle] = ['handle' => $handle, 'url' => $url, 'start' => microtime(true)];
    $dispatched++;
};

for ($i = 0; $i < $concurrency && $dispatched < $total; $i++) {
    $add();
}

do {
    curl_multi_exec($multi, $running);
    curl_multi_select($multi, 0.05);

    while ($info = curl_multi_info_read($multi)) {
        $handle = $info['handle'];
        $entry = $active[(int) $handle];
        $results[] = [
            'url' => $entry['url'],
            'status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
            'ms' => (microtime(true) - $entry['start']) * 1000,
            'bytes' => (int) curl_getinfo($handle, CURLINFO_SIZE_DOWNLOAD),
            'error' => curl_error($handle),
        ];
        curl_multi_remove_handle($multi, $handle);
        curl_close($handle);
        unset($active[(int) $handle]);

        if ($dispatched < $total) {
            $add();
        }
    }
} while ($running > 0 || $active !== []);

$elapsed = microtime(true) - $started;
$latencies = array_column($results, 'ms');
sort($latencies);
$pct = fn (float $p): float => $latencies[(int) min(count($latencies) - 1, floor($p * count($latencies)))];
$errors = count(array_filter($results, fn (array $r) => $r['status'] >= 500 || $r['status'] === 0 || $r['error'] !== ''));

printf("profile=%s base=%s concurrency=%d requests=%d\n", $profile, $base, $concurrency, count($results));
printf("elapsed %.1fs  throughput %.1f req/s  error rate %.2f%%  bytes %.1f MB\n", $elapsed, count($results) / $elapsed, 100 * $errors / max(1, count($results)), array_sum(array_column($results, 'bytes')) / 1048576);
printf("latency ms: min %.0f  p50 %.0f  p90 %.0f  p95 %.0f  p99 %.0f  max %.0f\n", $latencies[0], $pct(0.5), $pct(0.9), $pct(0.95), $pct(0.99), end($latencies));

$byStatus = array_count_values(array_column($results, 'status'));
ksort($byStatus);
echo 'status codes: '.json_encode($byStatus).PHP_EOL;

$byUrl = [];
foreach ($results as $r) {
    $byUrl[$r['url']][] = $r['ms'];
}
foreach ($byUrl as $url => $times) {
    sort($times);
    printf("  %-60s n=%3d p50=%5.0f p95=%5.0f\n", substr($url, strlen($base)), count($times), $times[(int) floor(0.5 * (count($times) - 1))], $times[(int) floor(0.95 * (count($times) - 1))]);
}
