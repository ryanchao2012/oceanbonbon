<?php
error_reporting(0);

// format data 為 hourly data
$hourly_data = [];
$file = fopen('vessels_50.csv', 'r');

if (!$file) {
    echo "error";
    exit;
}

while (!feof($file)) {
    list($tag, $time, $lon, $lat, $uuid, $type) = fgetcsv($file);
    $lat = (float) $lat;
    $lon = (float) $lon;

    if ('vessel' != $type) {
        continue;
    }

    $hour = strtotime(date("Y-m-d H:00:00", $time));

    if (!$hourly_data[$hour]) {
        $hourly_data[$hour] = [];
    }

    if (!$hourly_data[$hour][$uuid]) {
        $hourly_data[$hour][$uuid] = [
            'uuid' => $uuid,
            'hour' => $hour,
            'lat' => 0,
            'lon' => 0,
            'tag' => $tag,
            'total_lat' => 0,
            'total_lon' => 0,
            'count' => 0,
        ];
    }

    if (0 > $lon) {
        $lon = (float) 360 + $lon;
    }

    if (0 > $hourly_data[$hour][$uuid]['total_lon']) {
        $hourly_data[$hour][$uuid]['total_lon'] = (float) 360 + $hourly_data[$hour][$uuid]['total_lon'];
    }

    if ('trawling' == $hourly_data[$hour][$uuid]['tag']) {
        $tag = 'trawling';
    }

    if ('fishing' == $hourly_data[$hour][$uuid]['tag']) {
        $tag = 'fishing';
    }

    if ('outbound' == $hourly_data[$hour][$uuid]['tag']) {
        $tag = 'outbound';
    }

    $hourly_data[$hour][$uuid]['tag'] = $tag;
    $hourly_data[$hour][$uuid]['is_trawling'] = ('trawling' == $tag) ? 1 : 0;
    $hourly_data[$hour][$uuid]['total_lat'] += (float) $lat;
    $hourly_data[$hour][$uuid]['total_lon'] += (float) $lon;
    $hourly_data[$hour][$uuid]['count'] ++;
    $hourly_data[$hour][$uuid]['lat'] = ((float) $hourly_data[$hour][$uuid]['total_lat'] / $hourly_data[$hour][$uuid]['count']);
    $hourly_data[$hour][$uuid]['lon'] = ((float) $hourly_data[$hour][$uuid]['total_lon'] / $hourly_data[$hour][$uuid]['count']);

    if (180 < $hourly_data[$hour][$uuid]['lon']) {
        $hourly_data[$hour][$uuid]['lon'] = (float) (180 - ($hourly_data[$hour][$uuid]['lon'] - 180)) * -1;
    }
}
fclose($file);

$fp = fopen('vessel_data.json', 'w');
fwrite($fp, json_encode($hourly_data));
fclose($fp);
