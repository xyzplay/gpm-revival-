<?php
header("Content-Type: application/json");

echo json_encode([
    [0,2,2,0],
    [
        [
            [
                [
                    "something",
                    "Lab 1",
                    "Lab 2",
                    true
                ]
            ],
            [
[
    "1",
    0,
    0,
    "Nexus 6P",
    0,
    0,
    0,
    "Android 6.0 Marshmallow",
    "Connected since 2015"
]
            ],
            null,
            [
                0,
                "OP field 2",
                "OP field 3",
                0
            ],
            true
        ]
    ]
], JSON_PRETTY_PRINT);