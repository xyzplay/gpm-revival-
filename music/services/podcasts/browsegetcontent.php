<?php
header("Content-Type: application/json");

// get podcasts from dezzer
$data=json_decode(file_get_contents("https://api.deezer.com/chart"),true);

$podcasts=[];

foreach($data["podcasts"]["data"] as $podcast){

$id=(string)$podcast["id"];
$title=$podcast["title"];
$description=$podcast["description"];
$author="";
$image=$podcast["picture_xl"];

// make podcast cards
$podcasts[]=[
    "podcast-chart-card",
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

// podcasts response
$response=[
    [0,2,2,0],
    [
        $podcasts
    ],
    0
];

echo json_encode($response,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
