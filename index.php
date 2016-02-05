<?php

$forecast = NULL;  
function set_sunrise() {
    global $forecast;
    for ($i=0; $i < count($forecast["daily"]["data"]); $i++) {
         try {
           $forecast["daily"]["data"][$i]["sunriseTime"] = strftime("%I:%M %p", $forecast["daily"]["data"][$i]["sunriseTime"]);
         } catch (Exception $e) {
           $forecast["daily"]["data"][$i]["sunriseTime"] = 'N.A';
         }
    }
}

function set_sunset() {
    global $forecast;
    for ($i=0; $i < count($forecast["daily"]["data"]); $i++) {
         try {
           $forecast["daily"]["data"][$i]["sunsetTime"] = strftime("%I:%M %p", $forecast["daily"]["data"][$i]["sunsetTime"]);
         } catch (Exception $e) {
           $forecast["daily"]["data"][$i]["sunsetTime"] = 'N.A';
         }
    }
}

function set_hourly_time() {
    global $forecast;
    for ($i=0; $i < count($forecast["hourly"]["data"]); $i++) {
         try {
           $forecast["hourly"]["data"][$i]["time"] = strftime("%I:%M %p", $forecast["hourly"]["data"][$i]["time"]);
         } catch (Exception $e) {
           $forecast["hourly"]["data"][$i]["time"] = 'N.A';
         }
    }
}

function getForecast($street, $city, $state, $degree) {
    try {
        $queryParams = array('address'=>$street.','.$city.','.$state, 'key'=>'AIzaSyAJCGaVJQt8bZTtcGyoap3_vxcr8L3TfVg');
        $url = 'https://maps.google.com/maps/api/geocode/xml?'.http_build_query($queryParams);
        $xmlResponse = @file_get_contents($url);
        if ($xmlResponse === FALSE) {
            throw new Exception('Geocode API falied: '.error_get_last()['message']);
        }
        $geocodeResponse = new SimpleXMLElement($xmlResponse);
        if ($geocodeResponse->status == 'ZERO_RESULTS') {
            throw new Exception('No results returned, please try another address');
        }
        if ($geocodeResponse->status != 'OK') {
             throw new Exception("Wrong XML from Geocode API:  ".$xmlResponse);
        }
        $location = $geocodeResponse->result[0]->geometry->location;
        $lat = $location->lat;
        $long = $location->lng;
        $forecast_api_url = 'https://api.forecast.io/forecast/d033d0210cc294f0267b394c37765241/'.$lat.','.$long.'?units='.$degree.'&exclude=flags';
        global $forecast;
        $jsonResponse = @file_get_contents($forecast_api_url);
        if ($jsonResponse === FALSE) {
            throw new Exception('Forecast API falied'.error_get_last()['message']);
        }
        $forecast = json_decode($jsonResponse, true);
        if ($forecast == NULL) {
            throw new Exception("json decode failed".json_last_error());
        }
        date_default_timezone_set($forecast['timezone']);
        set_sunrise();
        set_sunset();
        set_hourly_time();
        unset($forecast['minutely'])
        return json_encode($forecast);
    } catch(Exception $e) {
        return 'Failed: '.$e->getMessage();
    }
}

header('content-type: application/json');
header('Access-Control-Allow-Origin: *');
    
if (isset($_GET["street"])) {
  echo getForecast($_GET["street"], $_GET["city"], $_GET["state"], $_GET["degree"]);
}
?>
