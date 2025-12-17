<?php
// app/Services/FigmaIconDetectorService.php

namespace App\Services;

class FigmaIconDetectorService
{
    /**
     * Entry point for icon detection
     */
    public function analyze(array $node): array
    {
        $document = $node['document'] ?? [];

        $isVectorOnly = $this->isVectorOnly($document);
        $isFixedSize = $this->isFixedSize($document);
        $hasNoAutoLayout = !$this->hasAutoLayout($document);
        $isColorFlexible = $this->isColorFlexible($document);
        $nameLooksLikeIcon = $this->nameLooksLikeIcon($document['name'] ?? '');

        $isIcon = $isVectorOnly
            && $isFixedSize
            && $hasNoAutoLayout
            && $isColorFlexible;

        return [
            'is_icon' => $isIcon,
            'rules' => [
                'vector_only' => $isVectorOnly,
                'fixed_size' => $isFixedSize,
                'no_auto_layout' => $hasNoAutoLayout,
                'color_flexible' => $isColorFlexible,
                'icon_naming' => $nameLooksLikeIcon,
            ],
            'dimensions' => $this->extractSize($document),
        ];
    }

    protected function isVectorOnly(array $node): bool
    {
        $allowed = ['VECTOR', 'BOOLEAN_OPERATION', 'GROUP'];

        if (!isset($node['children'])) return true;

        foreach ($node['children'] as $child) {
            if (!in_array($child['type'], $allowed)) {
                return false;
            }
            if (!$this->isVectorOnly($child)) {
                return false;
            }
        }
        return true;
    }

    protected function hasAutoLayout(array $node): bool
    {
        return isset($node['layoutMode']) && $node['layoutMode'] !== 'NONE';
    }

    protected function isFixedSize(array $node): bool
    {
        if (!isset($node['absoluteBoundingBox'])) return false;

        $w = (int) round($node['absoluteBoundingBox']['width']);
        $h = (int) round($node['absoluteBoundingBox']['height']);

        return in_array($w, [16, 20, 24, 32]) && $w === $h;
    }

    protected function isColorFlexible(array $node): bool
    {
        if (!isset($node['children'])) return true;

        foreach ($node['children'] as $child) {
            if (isset($child['fills'])) {
                foreach ($child['fills'] as $fill) {
                    if (($fill['type'] ?? '') === 'SOLID' && isset($fill['color'])) {
                        if (($fill['opacity'] ?? 1) === 1 && !$this->isStyleColor($fill)) {
                            return false;
                        }
                    }
                }
            }
            if (!$this->isColorFlexible($child)) return false;
        }
        return true;
    }

    protected function isStyleColor(array $fill): bool
    {
        return isset($fill['styleId']);
    }

    protected function nameLooksLikeIcon(string $name): bool
    {
        return preg_match('/icon|ic\b|glyph|pictogram/i', $name) === 1;
    }

    protected function extractSize(array $node): ?array
    {
        if (!isset($node['absoluteBoundingBox'])) return null;

        return [
            'width' => round($node['absoluteBoundingBox']['width']),
            'height' => round($node['absoluteBoundingBox']['height']),
        ];
    }
}
