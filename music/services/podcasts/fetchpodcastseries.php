<?php
header("Content-Type: application/json");

$url = "https://www.youtube.com/youtubei/v1/browse";
$cacheTime = 10800;

function phpErrLog($message)
{
    error_log($message);
}

function getValue($data, $path, $default = "")
{
  foreach ($path as $key) {
    if (!is_array($data) || !array_key_exists($key, $data)) {
      return $default;
    }

    $data = $data[$key];
  }

  return $data;
}

function getPlaylistId()
{
    $body = file_get_contents("php://input");
    $request = json_decode($body, true) ?: json_decode(urldecode($body), true);

    return is_string($request[1][0] ?? null) ? $request[1][0] : "";
}

function validYTMData($json)
{
    $data = json_decode($json, true);

    if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    $header = $data["header"]["pageHeaderRenderer"]["content"]["pageHeaderViewModel"] ?? null;
    $episodes = $data["contents"]["twoColumnBrowseResultsRenderer"]["tabs"][0]["tabRenderer"]["content"]["sectionListRenderer"]["contents"][0]["itemSectionRenderer"]["contents"] ?? null;

    return is_array($header) && is_array($episodes) ? $data : false;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
  http_response_code(405);
  header("Allow: POST");
  echo json_encode(["error" => "POST required"]);
  exit();
}

$playlistId = getPlaylistId();

if ($playlistId === "") {
  http_response_code(404);
  header("X-Rejected-Reason: PLAYLIST_ID_MISSING");
  echo json_encode([[0, 2, 2, 0], ["PLAYLIST_ID_MISSING"]]);
  exit();
}

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $playlistId)) {
  http_response_code(404);
  header("X-Rejected-Reason: PLAYLIST_ID_INVALID");
  echo json_encode([[0, 2, 2, 0], ["PLAYLIST_ID_INVALID"]]);
  exit();
}

$cacheDir = __DIR__ . "/" . $playlistId;
$cacheFile = $cacheDir . "/" . $playlistId . "_ytmpodcast_cache.json";
$cacheTemp = $cacheDir . "/" . $playlistId . "_ytmpodcast_cache.tmp";

if ((!is_dir($cacheDir) && !@mkdir($cacheDir, 0775, true)) || !is_writable($cacheDir)) {
    phpErrLog("Cache directory is not writable!");
}

$data = null;

if (is_file($cacheFile) && time() - filemtime($cacheFile) < $cacheTime) {
    $data = validYTMData(file_get_contents($cacheFile)) ?: null;
}

if ($data === null) {
    $payload = [
        "context" => [
            "client" => [
                "clientName" => "WEB",
                "clientVersion" => "2.20260708.00.00",
                "hl" => "en",
                "gl" => "US",
            ],
        ],
        "browseId" => "VL" . $playlistId,
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        phpErrLog("YTM cURL error: " . $curlError);
    } elseif ($httpCode === 200 && ($newData = validYTMData($response)) !== false) {
        $data = $newData;
        $written = @file_put_contents($cacheTemp, $response);

        if ($written !== strlen($response) || !@rename($cacheTemp, $cacheFile)) {
            @unlink($cacheTemp);
        }
    } else {
        phpErrLog($httpCode !== 200 ? "YTM HTTP error: " . $httpCode : "Invalid YTM response!");
    }

    if ($data === null && file_exists($cacheFile)) {
        $data = validYTMData(file_get_contents($cacheFile)) ?: null;
    }
}

if ($data === null) {
  phpErrLog("Unable to get YTM data!");
  http_response_code(500);
  header("X-Rejected-Reason: YTM_DATA_UNAVAILABLE");
  echo json_encode([[0, 2, 2, 0], ["YTM_DATA_UNAVAILABLE"]]);
  exit();
}

$header = $data["header"]["pageHeaderRenderer"]["content"]["pageHeaderViewModel"] ?? [];

$podcastTitle = $header["title"]["dynamicTextViewModel"]["text"]["content"] ?? "";
$podcastAuthor = $header["metadata"]["contentMetadataViewModel"]["metadataRows"][0]["metadataParts"][0]["avatarStack"]["avatarStackViewModel"]["text"]["content"] ?? "";
$podcastDescription = $header["description"]["descriptionPreviewViewModel"]["description"]["content"] ?? "";
$podcastImage = $header["heroImage"]["contentPreviewImageViewModel"]["image"]["sources"][2]["url"] ?? "";

$episodesData = $data["contents"]["twoColumnBrowseResultsRenderer"]["tabs"][0]["tabRenderer"]["content"]["sectionListRenderer"]["contents"][0]["itemSectionRenderer"]["contents"] ?? [];

$episodes = [];

foreach ($episodesData as $episode) {
    $lockup = $episode["lockupViewModel"] ?? null;

    if (!is_array($lockup)) {
        continue;
    }

    $metadata = $lockup["metadata"]["lockupMetadataViewModel"] ?? [];
    $episodeId = $lockup["contentId"] ?? "";
    $episodeTitle = $metadata["title"]["content"] ?? "";

    if ($episodeId === "" || $episodeTitle === "") {
        continue;
    }

    $episodeImage = $lockup["contentImage"]["thumbnailViewModel"]["image"]["sources"][3]["url"] ?? "";

    $episodes[] = [
        $episodeId,
        $episodeTitle,
        "",
        $podcastAuthor,
        0,
        "",
        "",
        [[$episodeImage, 0, "episode-cover"]],
        $podcastAuthor,
        null,
        null,
        null,
        null,
        null,
        "episode",
    ];
}

echo json_encode(
  [
    [0, 2, 2, 0],
    [
      [
        $playlistId,
        $podcastTitle,
        $podcastDescription,
        $podcastAuthor,
        [[$podcastImage, 0, "cover"]],
        "Podcast",
        $episodes,
        "",
        0,
        "",
      ],
      0,
    ],
  ],
  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);