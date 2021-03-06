<?php

// This class contains methods that directly access Omeka methods. In a Digital Archive installation,
// these methods are provided by AvantCommon's ItemMetadata class, but since AvantMapsAlive is designed
// to be used with no dependencies on other plugins, the methods need to exist here too.

class AvantMapsAlive
{
    public static function getElementIdForElementName($elementName)
    {
        $db = get_db();
        $elementTable = $db->getTable('Element');
        $element = $elementTable->findByElementSetNameAndElementName('Dublin Core', $elementName);
        if (empty($element))
            $element = $elementTable->findByElementSetNameAndElementName('Item Type Metadata', $elementName);
        return empty($element) ? 0 : $element->id;
    }

    public static function getElementNameForElementId($elementId)
    {
        $db = get_db();
        $element = $db->getTable('Element')->find($elementId);
        return isset($element) ? $element->name : '';
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
