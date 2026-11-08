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

function validChartData($json)
{
    $data=json_decode($json,true);
    if(json_last_error()!==JSON_ERROR_NONE){
        phpErrLog("JSON error: ".json_last_error_msg());
        return false;
    }
    if(!isset($data["contents"]["sectionListRenderer"]["contents"][0]["musicAnalyticsSectionRenderer"]["content"]["podcastShows"][0]["podcastShowEntries"])){
        return false;
    }

    return $data;
}

$data=null;

if(file_exists($cacheFile)&&time()-filemtime($cacheFile)<$cacheTime){
    $data=validChartData(file_get_contents($cacheFile));

    if($data===false){
        $data=null;
    }
}

if($data===null){
    $payload=[
        "context"=>[
            "client"=>[
                "clientName"=>"WEB_MUSIC_ANALYTICS",
                "clientVersion"=>"2.0",
                "hl"=>"en",
                "gl"=>"US",
                "experimentIds"=>[],
                "experimentsToken"=>"",
                "theme"=>"MUSIC"
            ],
            "capabilities"=>new stdClass(),
            "request"=>[
                "internalExperimentFlags"=>[]
            ]
        ],
        "browseId"=>"FEmusic_analytics_charts_home",
        "query"=>"flags=MusicCharts__enable_apac_and_shorts_charts_expansion&perspective=PODCAST_SHOW&chart_params_country_code=us&chart_params_chart_type=PODCAST_SHOWS_BY_WATCH_TIME&chart_params_period_type=WEEKLY"
    ];
    $ch=curl_init($url);
    curl_setopt_array($ch,[
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>json_encode($payload),
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_HTTPHEADER=>[
            "Content-Type: application/json",
            "x-goog-api-format-version: 2"
        ],
        CURLOPT_CONNECTTIMEOUT=>15,
        CURLOPT_TIMEOUT=>30
    ]);

    $response=curl_exec($ch);
    $httpCode=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $curlError=curl_error($ch);
    $curlErrno=curl_errno($ch);

    curl_close($ch);

    if($response===false){
        phpErrLog("cURL error ".$curlErrno.": ".$curlError);
    }elseif($httpCode!==200){
        phpErrLog("HTTP error: ".$httpCode);
        phpErrLog("Response: ".substr($response,0,3000));
    }else{
        $newData=validChartData($response);

        if($newData!==false){
            if(file_put_contents($cacheTemp,$response)!==false){
                if(rename($cacheTemp,$cacheFile)){
                    $data=$newData;
                }else{
                    phpErrLog("Unable to replace cache!");
                    @unlink($cacheTemp);
                }
            }else{
                phpErrLog("Unable to write to cache!");
            }
        }
    }
}

if($data===null){
    phpErrLog("Unable to get YTM chart data!");
    http_response_code(502);
    echo json_encode(["error"=>"Unable to request YTM charts!"]);
    exit;
}

$podcastEntries=$data["contents"]["sectionListRenderer"]["contents"][0]["musicAnalyticsSectionRenderer"]["content"]["podcastShows"][0]["podcastShowEntries"];

$podcasts=[];

foreach($podcastEntries as $podcast){
    $id=$podcast["externalPlaylistId"]??"";
    $title=$podcast["name"]??"";
    $description=$podcast["description"]["simpleText"]??"";
    $author=$podcast["channelName"]??"";
    $image=$podcast["thumbnail"]["thumbnails"][4]["url"]??"";

    $podcasts[]=[
        $id,
        $title,
        $description,
        $author,
        [
            [
                $image,
                0,
                "podcast-image"
            ]
        ],
        "Podcast",
        [
            [
                $id,
                $title,
                $description,
                $author,
                0,
                "",
                "",
                [
                    [
                        $image,
                        0,
                        "cover"
                    ]
                ],
                "A Podcast",
                null,
                null,
                null,
                null,
                null,
                "podcast"
            ]
        ],
        "",
        0
    ];
}

echo json_encode([
    [0,2,2,0],
    [
        $podcasts
    ],
    0
]);
