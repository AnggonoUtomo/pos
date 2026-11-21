<?php

namespace Tests\Unit\Modules\Platform\Hardware;

use App\Modules\Platform\Hardware\Domain\Services\LabelSpecification;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class LabelSpecificationTest extends TestCase
{
    public function test_label_dimensions_and_copies_are_bounded(): void
    {
        $this->assertSame(203, LabelSpecification::validate(['width_mm' => 50, 'height_mm' => 30, 'dpi' => 203, 'orientation' => 'portrait'])['dpi']);
        foreach ([['width_mm' => 101], ['height_mm' => 19], ['dpi' => 300], ['orientation' => 'raw'], ['width_mm' => 50.5]] as $invalid) {
            try {
                LabelSpecification::validate(array_replace(['width_mm' => 50, 'height_mm' => 30, 'dpi' => 203, 'orientation' => 'portrait'], $invalid));
                $this->fail('Invalid label accepted');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->expectException(InvalidArgumentException::class);
        LabelSpecification::copies(101);
    }
}
