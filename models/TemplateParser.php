<?php

class TemplateParser
{
    protected $rowNumber;

    public function convertTemplateToHtml($items, $text)
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

            $substitution = $this->replaceSubstitution($items, $substitution);

            $parsed .= substr($remaining, 0, $start);
            $parsed .= $substitution;
            $remaining = substr($remaining, $end);
        }

        return $parsed;
    }

    protected function parseRow($row, $convertElementNamesToIds)
    {
        $remaining = $row;
        $parsed = "";
        $done = false;

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
                    throw new Omeka_Validate_Exception(__('Closing brace is missing on line %s', $this->rowNumber));
                }
                else
                {
                    $end += 1;
                    $substitution = substr($remaining, $start, $end - $start);
                    $parsedSubstitution = $this->parseSubstitution($substitution, $convertElementNamesToIds);
                    $parsed .= substr($remaining, 0, $start);
                    $parsed .= $parsedSubstitution;
                    $remaining = substr($remaining, $end);
                }
            }
        }

        return $parsed;
    }

    protected function parseSubstitution($substitution, $convertElementNamesToIds)
    {
        $content = substr($substitution, 2, strlen($substitution) - 3);
        $parts = array_map('trim', explode(',', $content));
        $elementNameOrId = $parts[0];
        if ($elementNameOrId == 'file-url' || $elementNameOrId == 'item-url')
            return $substitution;

        if ($convertElementNamesToIds)
        {
            $elementId = AvantMapsAlive::getElementIdForElementName($elementNameOrId);
            if ($elementId == 0)
                throw new Omeka_Validate_Exception(__('"%s" on line %s is not an element.', $elementNameOrId, $this->rowNumber));

            $parts[0] = $elementId;
        }
        else
        {
            $elementName = AvantMapsAlive::getElementNameForElementId($elementNameOrId);
            $parts[0] = $elementName;
        }

        return '${' . implode(',', $parts) . '}';
    }

    public function parseTemplate($text, $convertElementNamesToIds)
    {
        $parsed = "";

        $rows = explode(PHP_EOL, $text);
        $this->rowNumber = 0;
        foreach ($rows as $row)
        {
            $this->rowNumber += 1;
            $parsedRow = $this->parseRow($row, $convertElementNamesToIds);
            $parsed .= $parsedRow . PHP_EOL;
        }

        return $parsed;
    }

    protected function replaceSubstitution($items, $substitution)
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
            $value = AvantMapsAlive::getItemFileUrl($item, $derivative, $fileIndex);
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
            $value = AvantMapsAlive::getElementTextFromElementId($item, $elementId);
        }
        return $value;
    }
}