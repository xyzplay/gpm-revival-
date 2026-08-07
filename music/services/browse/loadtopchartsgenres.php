<?php
// top chart genres
$topChartGenres = [
  [0, 2, 2, 0],
  [
    [
      ["1", "Alternative/Indie"],
      ["2", "Children's Music"],
      ["3", "Christian/Gospel"],
      ["4", "Classical"],
      ["5", "Country"],
      ["6", "Dance/Electronic"],
      ["7", "Hip Hop/Rap"],
      ["8", "Jazz"],
      ["9", "Latin"],
      ["10", "Metal"],
      ["11", "Pop"],
      ["12", "Rock"],
      ["13", "R&B"],
      ["14", "Soundtracks"],
      ["15", "Vocal/Easy listening"],
      ["16", "World"]
    ]
  ]
];
header("Content-Type: application/json");
echo json_encode($topChartGenres);
