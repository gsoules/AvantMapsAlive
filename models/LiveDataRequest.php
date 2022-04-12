<?php

class LiveDataRequest
{
    public function handleLiveDataRequest()
    {
        $user = current_user();

        if ($user && $user->role == 'super')
        {
            // This code is for development and testing. It allows a logged in super user to
            // simulate a remote request by putting the action and password on the query string.
            $siteId = isset($_GET['id']) ? $_GET['id'] : '';
            $action = isset($_GET['action']) ? $_GET['action'] : '';
            $password = isset($_GET['password']) ? $_GET['password'] : '';
        }
        else
        {
            $siteId = isset($_POST['id']) ? $_POST['id'] : '';
            $action = isset($_POST['action']) ? $_POST['action'] : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';
        }

        $response = 'TEST RESPONSE';

        switch ($action)
        {
            case 'garbage-collection':
                break;

            case 'ping':
                break;
        }

       return $response;
    }
}
