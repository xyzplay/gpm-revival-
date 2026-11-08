<?php
header('Content-Type: application/json');
// base innertube endpoint 
$url = 'https://youtubei.googleapis.com/youtubei/v1/browse';
$cacheFile = __DIR__ . '/ytmchart_innertube_cache.json';
$cacheTemp = __DIR__ . '/ytmchart_innertube_cache.tmp';
$cacheTime = 86400;

function phpErrLog($message)
{
  error_log('[ERROR] ' . $message);
}

function validChartData($data)
{
  return is_array($data) && isset($data['contents']);
}

$data = null;

if (file_exists($cacheFile)) {
    $age = time() - filemtime($cacheFile);

    if ($age < $cacheTime) {
        $cache = file_get_contents($cacheFile);

        if ($cache !== false) {
            $data = json_decode($cache, true);
        }

        if (!validChartData($data)) {
            phpErrLog('Cache invalid! Refreshing...');
            $data = null;
        }
    }
}

if ($data === null) {
    $payload = [
        'context' => [
            'client' => [
                'clientName' => 'WEB_MUSIC_ANALYTICS',
                'clientVersion' => '0.2',
                'hl' => 'en',
                'gl' => 'US',
                'experimentIds' => [],
                'experimentsToken' => '',
                'theme' => 'MUSIC'
            ],
            'capabilities' => new stdClass(),
            'request' => [
                'internalExperimentFlags' => []
            ]
        ],
        'browseId' => 'FEmusic_analytics_charts_home',
        'query' => 'chart_params_type=WEEK&perspective=CHART&flags=viral_video_chart&selected_chart=TRACKS&chart_params_id=weekly:0:0:US'
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-goog-api-format-version: 2'
        ],
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlErrno = curl_errno($ch);

    curl_close($ch);

    if ($response === false) {
        phpErrLog('cURL error ' . $curlErrno . ': ' . $curlError);
    } elseif ($httpCode !== 200) {
        phpErrLog('HTTP error: ' . $httpCode);
    } else {
        $newData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            phpErrLog('JSON error: ' . json_last_error_msg());
        } elseif (!validChartData($newData)) {
            phpErrLog('Invalid Innertube response!');
        } else {
            $written = file_put_contents($cacheTemp, $response);

            if ($written === false) {
                phpErrLog('Cannot write temporary cache!');
            } elseif (!rename($cacheTemp, $cacheFile)) {
                phpErrLog('Cannot replace cache file!');
                @unlink($cacheTemp);
            } else {
                $data = $newData;
            }
        }
    }
}

if ($data === null) {
    phpErrLog('Unable to get YTM chart data!');
    http_response_code(502);
    echo json_encode(['error' => 'Unable to request YTM charts!']);
    exit;
}

$albums = [];
$songs = [];

$trackViews = $data['contents']['sectionListRenderer']['contents'][0]['musicAnalyticsSectionRenderer']['content']['trackTypes'][0]['trackViews'] ?? [];

foreach ($trackViews as $track) {
    $id = $track['encryptedVideoId'] ?? '';
    $title = $track['name'] ?? '';
    $artist = $track['artists'][0]['name'] ?? '';
    $thumbnail = $track['thumbnail']['thumbnails'][3]['url'] ?? '';
    $position = $track['chartEntryMetadata']['currentPosition'] ?? 0;

    $albums[] = [
        $id,
        'Album',
        $artist,
        'Album',
        0,
        0,
        [],
        '',
        [],
        0,
        '',
        [],
        '',
        0,
        0,
        '',
        null,
        [],
        0,
        0,
        null,
        0,
        0
    ];

    $songs[] = [
        $id,
        $title,
        $thumbnail,
        $artist,
        'Album',
        $artist,
        null,
        null,
        null,
        null,
        null,
        null,
        null,
        1000,
        0,
        null,
        null,
        null,
        date('Y'),
        $position,
        0,
        0,
        0,
        0,
        0,
        0,
        0,
        0,
        0,
        0,
        'track'
    ];
}

echo json_encode([
    [0, 2, 2, 0],
    [
        [
            $albums,
            $songs
        ]
    ],
    0
]);
