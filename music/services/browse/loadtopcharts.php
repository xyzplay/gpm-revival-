<?php

header("Content-Type: application/json");
// use dezzer to get top charts
$data=json_decode(file_get_contents("https://api.deezer.com/chart"),true);

$albums=[];
$songs=[];

// top albums
foreach($data["albums"]["data"] as $album){
$albums[]=[
(string)$album["id"],
$album["title"],
$album["artist"]["name"],
$album["title"],
0,
0,
[],
"",
[],
0,
"",
[],
"",
0,
0,
"",
null,
[],
0,
0,
null,
0,
0
];
}

//  top songs
foreach($data["tracks"]["data"] as $track){
$songs[]=[
(string)$track["id"],
$track["title"],
$track["album"]["cover_xl"],
$track["artist"]["name"],
$track["album"]["title"],
"Artist",
null,
null,
null,
null,
null,
null,
null,
$track["duration"]*1000,
0,
null,
null,
null,
date("Y"),
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
0,
"track"
];
}

// create the top charts response
echo json_encode([
[0,2,2,0],
[
[
$albums,
$songs
]
],
0
]);
