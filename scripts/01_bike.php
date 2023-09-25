<?php
$bikeRawPath = dirname(__DIR__) . '/raw/bike';
if (!file_exists($bikeRawPath)) {
    mkdir($bikeRawPath, 0777, true);
}

$bikeDocsPath = dirname(__DIR__) . '/docs/bike';
if (!file_exists($bikeDocsPath)) {
    mkdir($bikeDocsPath, 0777, true);
}

$context = stream_context_create(
    array(
        "ssl" => array(
            "verify_peer" => false,
            "verify_peer_name" => false,
        ),
    )
);
$listFile = $bikeRawPath . '/list.html';
if (!file_exists($listFile)) {
    file_put_contents($listFile, file_get_contents('https://traffic.tainan.gov.tw/content/uploads/bicycle/cc_bicycle.html', false, $context));
}
$list = file_get_contents($listFile);
$pos = strpos($list, 'href="bicycle');
$fc = [
    'type' => 'FeatureCollection',
    'features' => [],
];
while (false !== $pos) {
    $pos += 6;
    $posEnd = strpos($list, '"', $pos);
    $fileName = substr($list, $pos, $posEnd - $pos);
    $rawFile = $bikeRawPath . '/' . $fileName;
    if (!file_exists($rawFile)) {
        file_put_contents($rawFile, file_get_contents('https://traffic.tainan.gov.tw/content/uploads/bicycle/' . $fileName, false, $context));
    }
    $raw = file_get_contents($rawFile);

    $rawPos = strpos($raw, 'https://www.google.com/maps/d/embed');
    $rawPosEnd = strpos($raw, '"', $rawPos);
    $parts = parse_url(substr($raw, $rawPos, $rawPosEnd - $rawPos));
    parse_str($parts['query'], $output);
    $rawKmlFile = $bikeRawPath . '/' . $output['mid'] . '.kml';
    if (!file_exists($rawKmlFile)) {
        file_put_contents($rawKmlFile, file_get_contents("https://www.google.com/maps/d/u/0/kml?mid={$output['mid']}&forcekml=1", false, $context));
    }
    $kml = new SimpleXMLElement(file_get_contents($rawKmlFile));
    foreach ($kml->Document->Folder as $f) {
        foreach ($f->Placemark as $p) {
            if (isset($p->LineString)) {
                $f = [
                    'type' => 'Feature',
                    'properties' => [
                        'name' => (string) $kml->Document->name,
                    ],
                    'geometry' => [
                        'type' => 'LineString',
                        'coordinates' => [],
                    ],
                ];
                $line = preg_split('/\s+/', $p->LineString->coordinates);
                foreach ($line as $point) {
                    $point = explode(',', $point);
                    if (empty($point[1])) {
                        continue;
                    }
                    $f['geometry']['coordinates'][] = [
                        floatval($point[0]),
                        floatval($point[1]),
                    ];
                }
                $fc['features'][] = $f;
            }
        }

    }
    $pos = strpos($list, 'href="bicycle', $posEnd);
}

file_put_contents($bikeDocsPath . '/lines.json', json_encode($fc, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));