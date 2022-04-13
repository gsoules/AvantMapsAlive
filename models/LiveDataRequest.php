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

        $template =  get_option(MapsAliveConfig::OPTION_TEMPLATES);
        $response = self::convertTemplateToHtml($items, $template);
        return $response;
    }

    private static function convertTemplateToHtml($items, $text)
    {
        $remaining = $text;
        $parsed = "";

        while (true)
        {
            $start = strpos($remaining, '${');

            if ($start == false)
            {
                $parsed .= $remaining;
                break;
            }

            $end = strpos($remaining, '}');
            $end += 1;
            $substitution = substr($remaining, $start, $end - $start);

            $substitution = self::replaceSubstitution($items, $substitution);

            $parsed .= substr($remaining, 0, $start);
            $parsed .= $substitution;
            $remaining = substr($remaining, $end);
        }

        return $parsed;
    }

    private static function replaceSubstitution($items, $substitution)
    {
        $content = substr($substitution, 2, strlen($substitution) - 3);
        $parts = array_map('trim', explode(',', $content));
        $elementId = $parts[0];

        if ($elementId == 'file-url')
        {
            $derivative = $parts[1];
            $itemIndex = count($parts) > 2 ? $parts[2] - 1 : 0;
            $fileIndex = count($parts) > 3 ? $parts[3] - 1 : 0;
            $item = $items[$itemIndex];
            $value = self::getItemFileUrl($item, $derivative, $fileIndex);
        }
        else if ($elementId == 'item-url')
        {
            $itemIndex = count($parts) > 1 ? $parts[1] - 1 : 0;
            $item = $items[$itemIndex];
            $value = WEB_ROOT . '/items/show/' . $item->id;
        }
        else
        {
            $itemIndex = count($parts) > 1 ? $parts[1] - 1 : 0;
            $item = $items[$itemIndex];
            $value = self::getElementTextFromElementId($item, $elementId);
        }
        return $value;
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

    public static function getItemFileUrl($item, $derivative, $fileIndex)
    {
        $url = '';
        $file = $item->getFile($fileIndex);
        if (!empty($file) && $file->hasThumbnail())
        {
            $url = $file->getWebPath($derivative);

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
