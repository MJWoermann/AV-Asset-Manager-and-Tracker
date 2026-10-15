<?php

namespace App\Support;

use InvalidArgumentException;
use SimpleXMLElement;
use ZipArchive;

class ShelfTagLbxParser
{
    public const NS_PT = 'http://schemas.brother.info/ptouch/2007/lbx/main';

    public const NS_STYLE = 'http://schemas.brother.info/ptouch/2007/lbx/style';

    public const NS_TEXT = 'http://schemas.brother.info/ptouch/2007/lbx/text';

    public const NS_IMAGE = 'http://schemas.brother.info/ptouch/2007/lbx/image';

    public const NS_BARCODE = 'http://schemas.brother.info/ptouch/2007/lbx/barcode';

    public const NS_DRAW = 'http://schemas.brother.info/ptouch/2007/lbx/draw';

    public const NS_META = 'http://schemas.brother.info/ptouch/2007/lbx/meta';

    public const NS_DC = 'http://purl.org/dc/elements/1.1/';

    /**
     * @return array{
     *     title: string|null,
     *     orientation: string,
     *     background: array{x: float, y: float, width: float, height: float},
     *     objects: list<array<string, mixed>>,
     *     files: array<string, string>
     * }
     */
    public function parse(string $absolutePath): array
    {
        if (! is_file($absolutePath)) {
            throw new InvalidArgumentException('LBX file not found.');
        }

        $zip = new ZipArchive;
        $opened = $zip->open($absolutePath);
        if ($opened !== true) {
            throw new InvalidArgumentException('Unable to open LBX archive.');
        }

        try {
            $labelXml = $zip->getFromName('label.xml');
            if ($labelXml === false || $labelXml === '') {
                throw new InvalidArgumentException('LBX archive is missing label.xml.');
            }

            $propXml = $zip->getFromName('prop.xml');
            $title = is_string($propXml) ? $this->parseTitle($propXml) : null;

            $document = $this->loadXml($labelXml);
            $sheet = $this->firstSheet($document);
            if ($sheet === null) {
                throw new InvalidArgumentException('LBX label.xml has no sheet.');
            }

            $styleNs = $sheet->children(self::NS_STYLE);
            // Avoid `$node[0] ?? null` — SimpleXML + isset/?? treats missing indexes as unset even when present.
            $paper = $this->firstChild($styleNs, 'paper');
            $background = $this->firstChild($styleNs, 'backGround');
            if ($background === null) {
                throw new InvalidArgumentException('LBX label.xml is missing style:backGround.');
            }

            $orientation = strtolower(trim($this->attr($paper, 'orientation', 'portrait')));
            if (! in_array($orientation, ['portrait', 'landscape'], true)) {
                $orientation = 'portrait';
            }

            $objectsNode = $this->firstChild($sheet->children(self::NS_PT), 'objects');
            $objects = $objectsNode !== null ? $this->parseObjects($objectsNode) : [];

            $files = [];
            foreach ($objects as $object) {
                if (($object['kind'] ?? '') !== 'image') {
                    continue;
                }

                $fileName = (string) ($object['fileName'] ?? '');
                if ($fileName === '' || str_contains($fileName, '/') || str_contains($fileName, '\\') || str_contains($fileName, '..')) {
                    continue;
                }

                $binary = $zip->getFromName($fileName);
                if (is_string($binary) && $binary !== '') {
                    $files[$fileName] = $binary;
                }
            }

            return [
                'title' => $title,
                'orientation' => $orientation,
                'background' => [
                    'x' => $this->ptAttr($background, 'x'),
                    'y' => $this->ptAttr($background, 'y'),
                    'width' => $this->ptAttr($background, 'width'),
                    'height' => $this->ptAttr($background, 'height'),
                ],
                'objects' => $objects,
                'files' => $files,
            ];
        } finally {
            $zip->close();
        }
    }

    protected function parseTitle(string $propXml): ?string
    {
        $xml = @simplexml_load_string($propXml);
        if (! $xml instanceof SimpleXMLElement) {
            return null;
        }

        $title = trim((string) ($xml->children(self::NS_DC)->title ?? ''));

        return $title !== '' ? $title : null;
    }

    protected function loadXml(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $document instanceof SimpleXMLElement) {
            throw new InvalidArgumentException('LBX label.xml is not valid XML.');
        }

        return $document;
    }

    protected function firstSheet(SimpleXMLElement $document): ?SimpleXMLElement
    {
        $body = $this->firstChild($document->children(self::NS_PT), 'body');
        if ($body === null) {
            return null;
        }

        return $this->firstChild($body->children(self::NS_STYLE), 'sheet');
    }

    protected function firstChild(?SimpleXMLElement $parent, string $name): ?SimpleXMLElement
    {
        if ($parent === null) {
            return null;
        }

        foreach ($parent->{$name} as $child) {
            if ($child instanceof SimpleXMLElement) {
                return $child;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function parseObjects(SimpleXMLElement $objectsNode): array
    {
        $objects = [];

        foreach ($this->childrenOf($objectsNode, self::NS_TEXT, 'text') as $text) {
            $objects[] = $this->parseTextObject($text);
        }

        foreach ($this->childrenOf($objectsNode, self::NS_IMAGE, 'image') as $image) {
            $objects[] = $this->parseImageObject($image);
        }

        foreach ($this->childrenOf($objectsNode, self::NS_BARCODE, 'barcode') as $barcode) {
            $objects[] = $this->parseBarcodeObject($barcode);
        }

        $drawRoot = $objectsNode->children(self::NS_DRAW);
        if ($drawRoot !== null) {
            foreach ($drawRoot->children() ?: [] as $tag => $draw) {
                if (! $draw instanceof SimpleXMLElement) {
                    continue;
                }
                $objects[] = $this->parseDrawObject((string) $tag, $draw);
            }
        }

        return $objects;
    }

    /**
     * @return list<SimpleXMLElement>
     */
    protected function childrenOf(SimpleXMLElement $parent, string $namespace, string $name): array
    {
        $nsChildren = $parent->children($namespace);
        if ($nsChildren === null) {
            return [];
        }

        $list = [];
        foreach ($nsChildren->{$name} as $node) {
            if ($node instanceof SimpleXMLElement) {
                $list[] = $node;
            }
        }

        return $list;
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseTextObject(SimpleXMLElement $text): array
    {
        $style = $this->firstChild($text->children(self::NS_PT), 'objectStyle');
        $expanded = $style !== null ? $this->firstChild($style->children(self::NS_PT), 'expanded') : null;
        $fontInfo = $this->firstChild($text->children(self::NS_TEXT), 'ptFontInfo');
        $logFont = $fontInfo !== null ? $this->firstChild($fontInfo->children(self::NS_TEXT), 'logFont') : null;
        $fontExt = $fontInfo !== null ? $this->firstChild($fontInfo->children(self::NS_TEXT), 'fontExt') : null;
        $align = $this->firstChild($text->children(self::NS_TEXT), 'textAlign');

        return array_merge($this->objectGeometry($style), [
            'kind' => 'text',
            'data' => trim((string) ($text->children(self::NS_PT)->data ?? '')),
            'fontSizePt' => $this->ptAttr($fontExt, 'size'),
            'fontWeight' => (int) $this->attr($logFont, 'weight', '400'),
            'align' => strtoupper($this->attr($align, 'horizontalAlignment', 'LEFT')),
            'color' => $this->attr($fontExt, 'textColor', '#000000'),
            'mergeType' => strtoupper($this->attr($expanded, 'templateMergeType', 'NONE')),
            'objectName' => $this->attr($expanded, 'objectName'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseImageObject(SimpleXMLElement $image): array
    {
        $style = $this->firstChild($image->children(self::NS_PT), 'objectStyle');
        $expanded = $style !== null ? $this->firstChild($style->children(self::NS_PT), 'expanded') : null;
        $imageStyle = $this->firstChild($image->children(self::NS_IMAGE), 'imageStyle');

        return array_merge($this->objectGeometry($style), [
            'kind' => 'image',
            'fileName' => $this->attr($imageStyle, 'fileName'),
            'originalName' => $this->attr($imageStyle, 'originalName'),
            'mergeType' => strtoupper($this->attr($expanded, 'templateMergeType', 'NONE')),
            'objectName' => $this->attr($expanded, 'objectName'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseBarcodeObject(SimpleXMLElement $barcode): array
    {
        $style = $this->firstChild($barcode->children(self::NS_PT), 'objectStyle');
        $expanded = $style !== null ? $this->firstChild($style->children(self::NS_PT), 'expanded') : null;
        $barcodeStyle = $this->firstChild($barcode->children(self::NS_BARCODE), 'barcodeStyle');
        $qrStyle = $this->firstChild($barcode->children(self::NS_BARCODE), 'qrcodeStyle');

        return array_merge($this->objectGeometry($style), [
            'kind' => 'barcode',
            'protocol' => strtoupper($this->attr($barcodeStyle, 'protocol')),
            'data' => trim((string) ($barcode->children(self::NS_PT)->data ?? '')),
            'humanReadable' => strtolower($this->attr($barcodeStyle, 'humanReadable', 'false')) === 'true',
            'eccLevel' => $this->attr($qrStyle, 'eccLevel'),
            'mergeType' => strtoupper($this->attr($expanded, 'templateMergeType', 'NONE')),
            'objectName' => $this->attr($expanded, 'objectName'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseDrawObject(string $tag, SimpleXMLElement $draw): array
    {
        $style = $this->firstChild($draw->children(self::NS_PT), 'objectStyle');
        $expanded = $style !== null ? $this->firstChild($style->children(self::NS_PT), 'expanded') : null;

        return array_merge($this->objectGeometry($style), [
            'kind' => 'draw',
            'shape' => strtolower($tag),
            'mergeType' => strtoupper($this->attr($expanded, 'templateMergeType', 'NONE')),
            'objectName' => $this->attr($expanded, 'objectName'),
        ]);
    }

    /**
     * @return array{x: float, y: float, width: float, height: float}
     */
    protected function objectGeometry(?SimpleXMLElement $style): array
    {
        return [
            'x' => $this->ptAttr($style, 'x'),
            'y' => $this->ptAttr($style, 'y'),
            'width' => $this->ptAttr($style, 'width'),
            'height' => $this->ptAttr($style, 'height'),
        ];
    }

    /**
     * Read an unprefixed attribute from a namespaced SimpleXML element.
     * Direct `$element['attr']` is unreliable for namespaced Brother LBX nodes.
     */
    protected function attr(?SimpleXMLElement $element, string $name, string $default = ''): string
    {
        if ($element === null) {
            return $default;
        }

        $attributes = $element->attributes();
        if ($attributes === null) {
            return $default;
        }

        $value = (string) $attributes[$name];

        return $value !== '' ? $value : $default;
    }

    protected function ptAttr(?SimpleXMLElement $element, string $attribute): float
    {
        return $this->parsePt($this->attr($element, $attribute, '0'));
    }

    public function parsePt(string $value): float
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return 0.0;
        }

        if (str_ends_with(strtolower($trimmed), 'pt')) {
            $trimmed = substr($trimmed, 0, -2);
        }

        return (float) $trimmed;
    }
}
