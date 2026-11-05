<?php
/*
 * Puts a static ffmpeg at bin/ffmpeg while the app builds.
 *
 * Laravel Cloud's image has no ffmpeg and no way to ask for a system package
 * (2026-11-06: /deploy-check said videoTool false), so without this every video
 * is stored as filmed: full size, no thumbnail. Composer calls this after the
 * autoloader is written, which is the one step every build runs.
 *
 * A single file, pinned to a release and checked against its hash, so a build
 * never runs something it did not expect. Anything going wrong is said out loud
 * and the build carries on: the app knows how to live without the tool, and a
 * failed download must not stop a deploy.
 */
const FFMPEG_RELEASE = 'b6.1.1';
const FFMPEG_BUILDS = [
    'x86_64' => ['ffmpeg-linux-x64.gz', 'bfe8a8fc511530457b528c48d77b5737527b504a3797a9bc4866aeca69c2dffa'],
    'aarch64' => ['ffmpeg-linux-arm64.gz', '754a678672298bc68156adff58aa7385a592c2b30b1d0ae8750c45c915c4bac0'],
];

function ffmpeg_say(string $words): void
{
    fwrite(STDOUT, '[ffmpeg] ' . $words . PHP_EOL);
}

function ffmpeg_download(string $url, string $to): void
{
    if (function_exists('curl_init')) {
        $out = fopen($to, 'wb');
        $c = curl_init($url);
        curl_setopt_array($c, [CURLOPT_FILE => $out, CURLOPT_FOLLOWLOCATION => true, CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 200, CURLOPT_FAILONERROR => true, CURLOPT_USERAGENT => 'anee-build']);
        $ok = curl_exec($c);
        $why = curl_error($c);
        fclose($out);
        if (! $ok) {
            throw new RuntimeException('download failed: ' . $why);
        }

        return;
    }

    $in = @fopen($url, 'rb', false, stream_context_create(['http' => ['follow_location' => 1, 'timeout' => 200, 'user_agent' => 'anee-build']]));
    if (! $in) {
        throw new RuntimeException('download failed: no curl and the stream would not open');
    }
    $out = fopen($to, 'wb');
    stream_copy_to_stream($in, $out);
    fclose($in);
    fclose($out);
}

$dir = __DIR__;
$target = $dir . '/ffmpeg';
$stamp = $dir . '/ffmpeg.release';
$gz = null;

try {
    // A developer's Windows machine has its own, and a Linux binary is no use to it.
    if (PHP_OS_FAMILY !== 'Linux' && ! getenv('FFMPEG_FETCH_ANYWAY')) {
        exit(0);
    }
    if (is_file($target) && is_file($stamp) && trim((string) file_get_contents($stamp)) === FFMPEG_RELEASE) {
        ffmpeg_say('already here, ' . FFMPEG_RELEASE);
        exit(0);
    }

    $arch = strtolower(php_uname('m'));
    $arch = ['amd64' => 'x86_64', 'arm64' => 'aarch64'][$arch] ?? $arch;
    if (! isset(FFMPEG_BUILDS[$arch])) {
        throw new RuntimeException('no build for ' . $arch);
    }
    [$name, $hash] = FFMPEG_BUILDS[$arch];

    $gz = tempnam(sys_get_temp_dir(), 'ffgz');
    ffmpeg_download('https://github.com/eugeneware/ffmpeg-static/releases/download/' . FFMPEG_RELEASE . '/' . $name, $gz);
    if (! hash_equals($hash, (string) hash_file('sha256', $gz))) {
        throw new RuntimeException('the download does not match its hash, so it was thrown away');
    }

    $from = gzopen($gz, 'rb');
    $to = fopen($target . '.tmp', 'wb');
    while (! gzeof($from)) {
        fwrite($to, (string) gzread($from, 1048576));
    }
    gzclose($from);
    fclose($to);
    chmod($target . '.tmp', 0755);
    rename($target . '.tmp', $target);
    file_put_contents($stamp, FFMPEG_RELEASE . PHP_EOL);
    ffmpeg_say('fetched ' . FFMPEG_RELEASE . ' for ' . $arch . ', ' . round(filesize($target) / 1048576) . ' MB');
} catch (Throwable $e) {
    @unlink($target . '.tmp');
    ffmpeg_say('not fetched, videos stay as filmed: ' . $e->getMessage());
} finally {
    if ($gz) {
        @unlink($gz);
    }
}

exit(0);
