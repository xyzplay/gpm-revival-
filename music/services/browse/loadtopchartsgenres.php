<?php
// top chart genres
$topChartGenres=[[0,2,2,0],[[[["pop","Pop"],["hip_hop","Hip-Hop"],["rap","Rap"],["rnb","R&B"],["soul","Soul"],["funk","Funk"],["jazz","Jazz"],["blues","Blues"],["country","Country"],["folk","Folk"],["classical","Classical"],["electronic","Electronic"],["edm","EDM"],["house","House"],["techno","Techno"],["trance","Trance"],["dubstep","Dubstep"],["metal","Metal"],["heavy_metal","Heavy Metal"],["punk","Punk"],["indie","Indie"],["alternative","Alternative"],["grunge","Grunge"],["reggae","Reggae"],["ska","Ska"],["latin","Latin"],["salsa","Salsa"],["reggaeton","Reggaeton"],["kpop","K-Pop"],["jpop","J-Pop"],["soundtrack","Soundtrack"],["gospel","Gospel"],["christian","Christian"],["new_age","New Age"],["ambient","Ambient"],["lofi","Lo-Fi"],["bluegrass","Bluegrass"],["opera","Opera"],["world","World Music"]]]]];
header("Content-Type: application/json");
echo json_encode($topChartGenres);
