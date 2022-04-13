<?php

class LiveDataRequest
{
    public function handleLiveDataRequest()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : 0;

        if ($id == 0)
            return "No item Id";

        $identifiers = explode(',', $id);

        $identifierElementId = AvantMapsAlive::getElementIdForElementName("Identifier");

        $items = [];
        foreach ($identifiers as $identifier)
        {
            $records = get_records('Item', array('search' => '', 'advanced' => array(array('element_id' => $identifierElementId, 'type' => 'is exactly', 'terms' => $identifier))));
            if (empty($records))
                $items[] = null;
            else
                $items[] = $records[0];
        }

        $template =  get_option(MapsAliveConfig::OPTION_TEMPLATES);

        $parser = new TemplateParser();
        $response = $parser->convertTemplateToHtml($items, $template);

        return $response;
    }
}
