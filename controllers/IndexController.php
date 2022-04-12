<?php

class AvantMapsAlive_IndexController extends Omeka_Controller_AbstractActionController
{
    public function liveDataAction()
    {
        $request = new LiveDataRequest();
        $response = $request->handleLiveDataRequest();
        $this->view->response = $response;
    }
}
