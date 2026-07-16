<?php

namespace App\Domains\Map\Services;

class MapCoordinateService
{
    public function normalize(float $x, float $y): array
    {
        return [
            'x_coordinate' => max(0, min(100, round($x, 4))),
            'y_coordinate' => max(0, min(100, round($y, 4))),
        ];
    }

    public function fromPixelPosition(
        float $clickX,
        float $clickY,
        float $containerWidth,
        float $containerHeight
    ): array {
        if ($containerWidth <= 0 || $containerHeight <= 0) {
            throw new \InvalidArgumentException('Invalid map container size.');
        }

        return $this->normalize(
            ($clickX / $containerWidth) * 100,
            ($clickY / $containerHeight) * 100
        );
    }
}
