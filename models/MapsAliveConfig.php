<?php

class MapsAliveConfig
{
    const OPTION_TEMPLATES = 'avantmapsalive_templates';

    private static function getElementIdForElementName($elementName)
    {
        $db = get_db();
        $elementTable = $db->getTable('Element');
        $element = $elementTable->findByElementSetNameAndElementName('Dublin Core', $elementName);
        if (empty($element))
            $element = $elementTable->findByElementSetNameAndElementName('Item Type Metadata', $elementName);
        return empty($element) ? 0 : $element->id;
    }

    private static function getElementNameForElementId($elementId)
    {
        $db = get_db();
        $element = $db->getTable('Element')->find($elementId);
        return isset($element) ? $element->name : '';
    }

    public static function getOptionTextForTemplates()
    {
        $text = get_option(self::OPTION_TEMPLATES);
        $parsed = self::parseTemplate($text, false);
        return $parsed;
    }

    private static function parseTemplate($text, $convertElementNamesToIds)
    {
        $remaining = $text;
        $parsed = "";
        $done = false;
        $error = "";

        while (!$done)
        {
            $start = strpos($remaining, '${');
            if ($start == false)
            {
                $done = true;
                $parsed .= $remaining;
            }
            else
            {
                $end = strpos($remaining, '}');
                if ($end == false)
                {
                    throw new Omeka_Validate_Exception(__('Closing brace is missing'));
                }
                else
                {
                    $end += 1;
                    $substitution = substr($remaining, $start, $end - $start);

                    $substitution = self::parseSubstitution($substitution, $convertElementNamesToIds);

                    $parsed .= substr($remaining, 0, $start);
                    $parsed .= $substitution;
                    $remaining = substr($remaining, $end);
                }
            }
        }

        return $parsed;
    }

    private static function parseSubstitution($substitution, $convertElementNamesToIds)
    {
        $content = substr($substitution, 2, strlen($substitution) - 3);
        $parts = array_map('trim', explode(',', $content));
        $elementNameOrId = $parts[0];
        if ($elementNameOrId == 'file-url' || $elementNameOrId == 'item-url')
            return $substitution;

        if ($convertElementNamesToIds)
        {
            $elementId = self::getElementIdForElementName($elementNameOrId);
            if ($elementId == 0)
                throw new Omeka_Validate_Exception('Error' . __('"%s" is not an element.', $elementNameOrId));

            $parts[0] = $elementId;
        }
        else
        {
            $elementName = self::getElementNameForElementId($elementNameOrId);
            $parts[0] = $elementName;
        }

        return '${' . implode(',', $parts) . '}';
    }

    public static function saveConfiguration()
    {
        self::saveOptionTextForTemplates();
    }

    public static function saveOptionTextForTemplates()
    {
        $text = $_POST[self::OPTION_TEMPLATES];
        $parsed = self::parseTemplate($text, true);
        set_option(self::OPTION_TEMPLATES, $parsed);
    }
}