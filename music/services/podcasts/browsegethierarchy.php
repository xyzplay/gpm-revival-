<?php
header("Content-Type: application/json");
// podcasts
$podcasts = [
  [0,2,2,0],
  [
    [
      [
        "something-1",
        "podcasts",
        [
          ["top-podcasts","Top Podcasts",[]],
          ["top-episodes","Top Episodes",[]]
        ]
      ]
    ]
  ],
  0
];
echo json_encode($podcasts);
