<?php

// Allow cross-origin requests.
header('Access-Control-Allow-Origin: *');

// Return the response from AvantMapsAlive_IndexController::liveDataAction().
echo $response;
