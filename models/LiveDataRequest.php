<?php

class LiveDataRequest
{
    public function handleLiveDataRequest()
    {
        $itemIdentifiers = isset($_GET['id']) ? $_GET['id'] : 0;
        if ($itemIdentifiers == 0)
            return "No item identifier(s) provided";

        $templateName = isset($_GET['template']) ? $_GET['template'] : "";
        if ($templateName == "")
            return "No template name provided";

        $identifiers = explode(',', $itemIdentifiers);

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

        $parser = new TemplateParser();
        $response = $parser->convertTemplateToHtml($items, $templateName);

        return $response;
    }
}
