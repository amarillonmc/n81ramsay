<?php
/**
 * 作者榜缓存失效轻量测试
 *
 * 运行方式：php tests/author_cache_test.php
 */

require_once __DIR__ . '/../includes/Models/AuthorStats.php';

$cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ramsay_author_cache_' . uniqid('', true);
mkdir($cacheDir, 0755, true);

$cacheFile = $cacheDir . '/author_hall_of_fame.json';
$metadataFile = $cacheDir . '/author_hall_of_fame.meta.json';
$reflection = new ReflectionClass('AuthorStats');
$authorStats = $reflection->newInstanceWithoutConstructor();
$cacheDirProperty = $reflection->getProperty('cacheDir');
$cacheDirProperty->setAccessible(true);
$cacheDirProperty->setValue($authorStats, $cacheDir);

$writeMethod = $reflection->getMethod('cacheAuthorStats');
$writeMethod->setAccessible(true);
$readMethod = $reflection->getMethod('readCachedAuthorStats');
$readMethod->setAccessible(true);

$expectedStats = ['测试作者' => ['total_cards' => 2]];
$fingerprint = hash('sha256', 'test-source');
$written = $writeMethod->invoke($authorStats, $expectedStats, $fingerprint);
$envelope = json_decode(file_get_contents($cacheFile), true);
$readStats = $readMethod->invoke($authorStats, $cacheFile, $fingerprint);
$wrongFingerprintStats = $readMethod->invoke($authorStats, $cacheFile, hash('sha256', 'other-source'));

$envelopePassed = $written
    && is_array($envelope)
    && isset($envelope['cache_version'], $envelope['source_fingerprint'], $envelope['generated_at'])
    && $envelope['cache_version'] === 2
    && $envelope['source_fingerprint'] === $fingerprint
    && $envelope['author_stats'] === $expectedStats
    && $readStats === $expectedStats
    && $wrongFingerprintStats === null;

file_put_contents($metadataFile, '{}');

$success = AuthorStats::invalidateCacheFiles($cacheDir);
$passed = $envelopePassed && $success && !file_exists($cacheFile) && !file_exists($metadataFile);

if (!$passed) {
    @unlink($cacheFile);
    @unlink($metadataFile);
    @unlink($cacheDir . '/author_hall_of_fame.lock');
    @rmdir($cacheDir);
    echo "[FAIL] 作者榜缓存应原子保存单文件封套，并隔离清理测试缓存\n";
    exit(1);
}

@unlink($cacheDir . '/author_hall_of_fame.lock');
@rmdir($cacheDir);
echo "[PASS] 作者榜缓存原子保存单文件封套，并隔离清理测试缓存\n";
