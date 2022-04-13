<?php

class LiveDataRequest
{
    public function handleLiveDataRequest()
    {
        $id = isset($_GET['id']) ? $_GET['id'] : 0;

        if ($id == 0)
            return "No item Id";

        $identifiers = explode(',', $id);

        $identifierElementId = self::getElementIdForElementName("Identifier");

        $items = [];
        foreach ($identifiers as $identifier)
        {
            $records = get_records('Item', array('search' => '', 'advanced' => array(array('element_id' => $identifierElementId, 'type' => 'is exactly', 'terms' => $identifier))));
            if (empty($records))
                $items[] = null;
            else
                $items[] = $records[0];
        }

        $titleElementId = self::getElementIdForElementName("Title");
        $title = self::getElementTextFromElementId($items[0], $titleElementId);

        $descriptionElementId = self::getElementIdForElementName("Description");
        $description = self::getElementTextFromElementId($items[0], $descriptionElementId);

        $itemImageUrl = self::getItemFileUrl($items[0]);

        $itemId = $items[0]->id;
        $itemUrl = WEB_ROOT . '/items/show/' . $itemId;

        $template = MapsAliveConfig::getOptionTextForTemplates();
        $response = str_replace('${Title}', $title, $template);
        $response = str_replace('${Description}', $description, $response);
        $response = str_replace('${file-url}', $itemImageUrl, $response);
        $response = str_replace('${item-url}', $itemUrl, $response);

        return $response;
    }

    public static function getElementIdForElementName($elementName)
    {
        $db = get_db();
        $elementTable = $db->getTable('Element');
        $element = $elementTable->findByElementSetNameAndElementName('Dublin Core', $elementName);
        if (empty($element))
            $element = $elementTable->findByElementSetNameAndElementName('Item Type Metadata', $elementName);
        return empty($element) ? 0 : $element->id;
    }

    public static function getElementTextFromElementId($item, $elementId, $asHtml = true)
    {
        $db = get_db();
        $element = $db->getTable('Element')->find($elementId);
        $text = '';
        if (!empty($element))
        {
            $texts = $item->getElementTextsByRecord($element);
            $text = isset($texts[0]['text']) ? $texts[0]['text'] : '';
        }
        return $asHtml ? html_escape($text) : $text;
    }

    public static function getItemFileUrl($item)
    {
        $url = '';
        $file = $item->getFile(0);
        if (!empty($file) && $file->hasThumbnail())
        {
            $url = $file->getWebPath('fullsize');

            $supportedImageMimeTypes = self::supportedImageMimeTypes();

            if (!in_array($file->mime_type, $supportedImageMimeTypes))
            {
                // The original image is not a jpg (it's probably a pdf) so return its derivative image instead.
                $url = $file->getWebPath('fullsize');
            }
        }

        return $url;
    }

    public static function supportedImageMimeTypes()
    {
        return array(
            'image/jpg',
            'image/jpeg',
            'image/png'
        );
    }

}
