<?php

class AvantMapsAlive_IndexController extends Omeka_Controller_AbstractActionController
{
    public function fooAction()
    {
        $request = new LiveDataRequest();
        $response = $request->handleLiveDataRequest();
        $this->view->response = $response;
    }
}
