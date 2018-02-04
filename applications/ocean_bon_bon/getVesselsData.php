<?php
error_reporting(0);
header('Content-Type: application/json');

$interval = (int) $_GET['interval'];
$begin_date = strtotime(urldecode($_GET['start_at']));
$end_date = strtotime(urldecode($_GET['end_at']));

if (!$interval or !$begin_date or !$end_date) {
    echo "error";
    exit;
}

$vessel_data = [];

$hourly_data = json_decode(file_get_contents('vessel_data.json'), 1);

if (!$hourly_data) {
    echo "no data";
    exit;
}

ksort($hourly_data);
echo json_encode($hourly_data);
exit;
// $count_data = [];
//foreach ($hourly_data as $key => $data) {
  //  $count_data[date('Y-m-d_H:i:s', $key)] = count($data);
//}
// echo json_encode($count_data);exit;

//  每一筆資料即為一天的資料
for ($hour = $begin_date; $hour < $end_date; $hour = $hour + 3600) {
    $start_time = $hour - ($interval * 3600);
    $end_time = $hour;

    foreach ($hourly_data as $data_uuid => $boat_data) {
        foreach ($boat_data as $data) {
            $uuid = $data['uuid'];
            $hour_time = $data['hour'];
            $lat = (float) $data['lat'];
            $lon = (float) $data['lon'];
            $is_trawling = $data['is_trawling'];

            if ($hour_time < $start_time) {
                continue;
            }

            if ($hour_time > $hour) {
                break;
            }

            if (!$vessel_data[$hour]) {
                $vessel_data[$hour] = [];
            }

            if (!$vessel_data[$hour][$uuid]) {
                $vessel_data[$hour][$uuid] = [
                    'total_lat' => 0,
                    'total_lon' => 0,
                    'count' => 0,
                    'lat' => 0,
                    'lon' => 0
                ];
            }

            if (0 > $lon) {
                $lon = (float) 360 + $lon;
            }

            if (0 > $vessel_data[$hour][$uuid]['total_lon']) {
                $vessel_data[$hour][$uuid]['total_lon'] = (float) 360 + $vessel_data[$hour][$uuid]['total_lon'];
            }

            $vessel_data[$hour][$uuid]['total_lat'] = (float) $vessel_data[$hour][$uuid]['total_lat'] + $lat;
            $vessel_data[$hour][$uuid]['total_lon'] = (float) $vessel_data[$hour][$uuid]['total_lon'] + $lon;
            $vessel_data[$hour][$uuid]['count'] = $vessel_data[$hour][$uuid]['count'] + 1;
            $vessel_data[$hour][$uuid]['lat'] = (float) ($vessel_data[$hour][$uuid]['total_lat'] / $vessel_data[$hour][$uuid]['count']);
            $vessel_data[$hour][$uuid]['lon'] = (float) ($vessel_data[$hour][$uuid]['total_lon'] / $vessel_data[$hour][$uuid]['count']);
            $vessel_data[$hour][$uuid]['is_trawling'] = $is_trawling;

            if ($vessel_data[$hour][$uuid]['lon'] > 180) {
                $vessel_data[$hour][$uuid]['lon'] = (float) (180 - ($vessel_data[$hour][$uuid]['lon'] - 180)) * -1;
            }
        }
    }
}

echo json_encode($vessel_data);
