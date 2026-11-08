<?php
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);

$query = "";

if (is_array($input)) {
    // Your format: [["id",1],["query"]]
    if (isset($input[1][0]) && is_string($input[1][0])) {
        $query = trim($input[1][0]);
    }
    // Fallback for simple ["query"] format
    elseif (isset($input[0]) && is_string($input[0])) {
        $query = trim($input[0]);
    }
}

$suggestions = [];

if ($query !== "") {

    $url = "https://suggestqueries.google.com/complete/search?client=youtube&ds=yt&q=" . urlencode($query);

    $response = @file_get_contents($url);

    if ($response !== false) {

        // Remove JSONP wrapper if present
        $response = preg_replace('/^window\.google\.ac\.h\(|\)$/', '', $response);

        $data = json_decode($response, true);

        foreach ($data[1] ?? [] as $item) {
            if (isset($item[0])) {
                $suggestions[] = [
                    2,
                    $item[0]
                ];
            }
        }
    }
}

echo json_encode(
    [
        [0,2,2,0],
        $suggestions
    ],
    JSON_PRETTY_PRINT
);