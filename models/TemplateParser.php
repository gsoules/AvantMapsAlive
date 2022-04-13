<?php

class TemplateParser
{
    protected $rowNumber;
    protected $templateName = "";
    protected $parsedTextTemplates = [];

    public function convertTemplateToHtml($items, $templateName)
    {
        $raw =  get_option(MapsAliveConfig::OPTION_TEMPLATES);
        $templates = json_decode($raw, true);

        if (!array_key_exists($templateName, $templates))
            return "No such template name";

        $rows = $templates[$templateName];

        $html = "";

        foreach ($rows as $row)
        {
            $remaining = $row;
            while (true)
            {
                // Look for a substitution in the remaining text on this row.
                $start = strpos($remaining, '${');
                if ($start === false)
                {
                    // There's no substitution. Keep the rest of the row text and go onto the next.
                    $html .= $remaining;
                    break;
                }
                $end = strpos($remaining, '}');
                $end += 1;

                // Get the substitution including the ${...} wrapper.
                $substitution = substr($remaining, $start, $end - $start);

                // Replace the entire substitution with a data value.
                $replacement = $this->replaceSubstitution($items, $substitution);
                $html .= substr($remaining, 0, $start);
                $html .= $replacement;

                $remaining = substr($remaining, $end);
            }
        }

        return $html;
    }

    protected function parseTemplateRow($row, $convertElementNamesToIds)
    {
        // This method converts a text template row that contains elements names to a json template row that
        // contains element Ids. It also converts a json template row that contains element Ids to a text
        // template row that contains element names.

        $remaining = $row;
        $parsed = "";
        $done = false;

        while (!$done)
        {
            $start = strpos($remaining, '${');
            if ($start === false)
            {
                $done = true;
                $parsed .= $remaining;
            }
            else
            {
                $end = strpos($remaining, '}');
                if ($end === false)
                {
                    throw new Omeka_Validate_Exception(__('Closing brace is missing in template %s on line %s', $this->templateName, $this->rowNumber));
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

    protected function parseTextTemplateDefinition($row, $index)
    {
        $this->templateName = trim(substr($row, $index + 9));
        $index = strpos($this->templateName, ' ');
        if ($index !== false)
            $this->templateName = substr($this->templateName, 0, $index);
    }

    protected function parseTextTemplateRows($templateName, $rows)
    {
        $this->parsedTextTemplates[$templateName] = [];
        $this->templateName = $templateName;
        $this->rowNumber = 0;
        foreach ($rows as $row)
        {
            $this->rowNumber += 1;
            $parsedRow = $this->parseTemplateRow($row, true);
            $this->parsedTextTemplates[$templateName][] = $parsedRow;
        }
    }

    protected function parseSubstitution($substitution, $convertElementNamesToIds)
    {
        // This method converts a substitution value that is within ${...} to either use an element name or element Id.

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

    public function parseTextTemplates($text)
    {
        $templates = [];
        $rows = explode(PHP_EOL, $text);

        foreach ($rows as $row)
        {
            if (trim($row) == "")
                continue;

            $index = strpos(strtolower($row), 'template:');
            $isTemplateDefinitionRow = $index !== false;
            if ($isTemplateDefinitionRow)
            {
                $this->parseTextTemplateDefinition($row, $index);
                $templates[$this->templateName] = [];
                continue;
            }

            $templates[$this->templateName][] = $row;
        }

        foreach ($templates as $templateName => $rows)
        {
            $this->parseTextTemplateRows($templateName, $rows);
        }

        return json_encode($this->parsedTextTemplates);
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

    public function unparseJsonTemplates($json)
    {
        $text = "";
        $templates = json_decode($json, true);

        foreach ($templates as $templateName => $rows)
        {
            $text .= "Template: $templateName";
            foreach ($rows as $row)
            {
                $parsedRow = $this->parseTemplateRow($row, false);
                $text .= PHP_EOL . $parsedRow;
            }
            $text .= PHP_EOL . PHP_EOL;
        }

        return $text;
    }
}