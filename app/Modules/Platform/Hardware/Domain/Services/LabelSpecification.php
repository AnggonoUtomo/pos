<?php

namespace App\Modules\Platform\Hardware\Domain\Services;

use InvalidArgumentException;

final class LabelSpecification
{
    public static function validate(array $input): array
    {
        foreach (['width_mm' => [20, 100], 'height_mm' => [20, 150], 'dpi' => [203, 203]] as $field => [$min, $max]) {
            $value = $input[$field] ?? null;
            if (! is_int($value) || $value < $min || $value > $max) {
                throw new InvalidArgumentException('Ukuran label atau DPI tidak didukung.');
            }
        }
        if (! in_array($input['orientation'] ?? null, ['portrait', 'landscape'], true)) {
            throw new InvalidArgumentException('Orientasi label tidak valid.');
        }

        return array_intersect_key($input, array_flip(['width_mm', 'height_mm', 'dpi', 'orientation']));
    }

    public static function copies(int $copies): int
    {
        if ($copies < 1 || $copies > 100) {
            throw new InvalidArgumentException('Jumlah label harus 1 sampai 100.');
        }

        return $copies;
    }
}
