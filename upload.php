<?php

require_once __DIR__ . "/upload-conf.php";

function send_request($target, $id = null, $custom_request = null, $url_params = null) { //$target is one of "Booking", "Desk", "Employee", etc
    global $project_url, $apiKey, $use_ntlm_auth, $ntlm_user, $ntlm_password;

    $id = ($id) ? "/" . $id : $id = "";
    if ($custom_request == 'POST') $url = "{$project_url}/api/{$target}{$id}?apikey={$apiKey}";
    else {
        $url_params = ($url_params) ? "&" . $url_params : "";
        $url = "{$project_url}/api/{$target}{$id}?apikey={$apiKey}{$url_params}";
    }
    $url = str_replace(' ', '%20', $url);
    // echo '<br>' . PHP_EOL . 'URL: ' . $url;

    function_exists('curl_init') ? 0 : die('ERROR: LIBRARY CURL IS NOT CONNECTED');
    $ch = curl_init();
    $ch = $ch ? $ch : die('ERROR: Cannot init the curl connection');

    $options = [
        CURLOPT_HTTPGET => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_URL => $url,
        CURLOPT_HEADER => false,
    ];
    if ($use_ntlm_auth) {
        $options[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC | CURLAUTH_NTLM;
        $options[CURLOPT_USERPWD] = sprintf('%s:%s', $ntlm_user, $ntlm_password);
        // echo '<br>' . PHP_EOL . 'User: ' . $ntlm_user . ' Password: ' . $ntlm_user;
    }
    if (preg_match("/^https:/i", $url)) {
        $options[CURLOPT_SSL_VERIFYHOST] = false;
        $options[CURLOPT_SSL_VERIFYPEER] = false;
    }
    if ($custom_request) {
        $options[CURLOPT_HTTPGET] = false;
        if ($custom_request == 'PUT') {
            $options[CURLOPT_PUT] = true;
        } else if ($custom_request == 'POST') {
            $options[CURLOPT_POST] = true;
            $url_params = str_replace(' ', '%20', $url_params);
            $options[CURLOPT_POSTFIELDS] = $url_params;
        } else {
            $options[CURLOPT_CUSTOMREQUEST] = $custom_request;
        }
    }

    curl_setopt_array($ch, $options);

    $json_response = curl_exec($ch);
    $response = json_decode($json_response, true);
    // echo '<br>' . PHP_EOL . 'Response: ' . $json_response;
    curl_close($ch);

    return $response;
}

function array_to_url($arr) {
    $res = '';
    $i = 0;
    foreach ($arr as $key => $value) {
        if ($value) {
            if ($i > 0) $res .= '&';
            $i += 1;
            $res .= $key . '=' . $value;
        }
    }
    return $res;
}

function add_desk_to_staffmap($data) {
    global $target_floor_id;
    if (isset($data['desk_id'])) unset($data['desk_id']);
    $data["floor_id"] = $target_floor_id;
    $url_params = array_to_url($data);
    $response = send_request('Desk', false, 'POST', $url_params);
    if (!isset($response[0]['id'])) return false;
    $desk_id = $response[0]['id'];
    // $res = send_request('Desk', $desk_id, 'PUT', $url_params);

    return $desk_id;
}

function add_asset_to_staffmap($data) {
    global $target_floor_id;
    if (isset($data['asset_id'])) unset($data['asset_id']);
    if (isset($data['date purchased'])) unset($data['date purchased']);
    $data["floor_id"] = $target_floor_id;
    $url_params = array_to_url($data);
    $response = send_request('Asset', false, 'POST', $url_params);
    if (!isset($response[0]['id'])) {
        echo PHP_EOL;
        var_dump($response);
        return false;
    }
    $asset_id = $response[0]['id'];
    // $res = send_request('Desk', $desk_id, 'PUT', $url_params);

    return $asset_id;
}

function remove_bad_sings($value) {
    $value = str_replace('"', '', $value);
    $value = str_replace("'", '', $value);
    $value = str_replace('/', urlencode("/"), $value);
    $value = str_replace('\\', urlencode("\\"), $value);
    return $value;
}

$oldToNewDeskIds = [];
if ($load_desks) {
    $fieldnames = [];
    $data = [];
    $i = 0;
    $file = fopen($csv_desks_filename, "r");
    while (($row = fgetcsv($file, 0, $csv_delimiter)) !== false) {
        $row_data = [];
        foreach ($row as $key => $value) {
            if ($i == 0) $fieldnames[] = remove_bad_sings(iconv($csv_charset, "utf-8", $value));
            else $row_data[$fieldnames[$key]] = remove_bad_sings(iconv($csv_charset, "utf-8", $value));
        }
        if ($i != 0) $data[] = $row_data;
        $i++;
    }
    fclose($file);

    $len = count($data);
    foreach ($data as $i => $row) {
        $oldId = $row["desk_id"];
        $res = add_desk_to_staffmap($row);
        if ($res) {
            echo PHP_EOL . "Loaded " . $i . " desks of " . $len . " id: " . $res;
            $oldToNewDeskIds[] = [$res, $oldId];
        } else echo PHP_EOL . "Failed to load desk " . $i;
    }
}
file_put_contents($oldToNewDeskIdsFilename, json_encode($oldToNewDeskIds));




if ($load_assets) {
    $fieldnames = [];
    $data = [];
    $i = 0;
    $file = fopen($csv_assets_filename, "r");
    while (($row = fgetcsv($file, 0, $csv_delimiter)) !== false) {
        $row_data = [];
        foreach ($row as $key => $value) {
            if ($i == 0) $fieldnames[] = remove_bad_sings(iconv($csv_charset, "utf-8", $value));
            else $row_data[$fieldnames[$key]] = remove_bad_sings(iconv($csv_charset, "utf-8", $value));
        }
        if ($i != 0) $data[] = $row_data;
        $i++;
    }
    fclose($file);

    $len = count($data);
    foreach ($data as $i => $row) {
        $res = add_asset_to_staffmap($row);
        if ($res) echo PHP_EOL . "Loaded " . $i . " assets of " . $len . " id: " . $res;
        else echo PHP_EOL . "Failed to load asset " . $i;
    }
}
