<?php

class LiveDataRequest
{
    public function handleLiveDataRequest()
    {
        $identifier = isset($_GET['id']) ? $_GET['id'] : 0;

        if ($identifier == 0)
            return "No item Id";

        $identifierElementId = self::getElementIdForElementName("Identifier");
        $items = get_records('Item', array('search' => '', 'advanced' => array(array('element_id' => $identifierElementId, 'type' => 'is exactly', 'terms' => $identifier))));
        if (empty($items))
            return "No item found for $identifier";
        $item = $items[0];

        if ($item == null)
            return "Not an item Id";

        $titleElementId = self::getElementIdForElementName("Title");
        $title = self::getElementTextFromElementId($item, $titleElementId);

        $descriptionElementId = self::getElementIdForElementName("Description");
        $description = self::getElementTextFromElementId($item, $descriptionElementId);

        $itemImageUrl = self::getItemFileUrl($item);

        $itemId = $item->id;
        $itemUrl = WEB_ROOT . '/items/show/' . $itemId;

        $response = "<div class='swhpl'><div class='title-element'>$title</div><div class='description-element'>$description</div><div><img src='$itemImageUrl'></div><div></div><a target='_blank' href='$itemUrl'>View this item</a></div>";

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
