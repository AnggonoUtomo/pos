<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Presentation\Resources;

use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeUnitAssignmentData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EmployeeUnitAssignmentData */
final class EmployeeUnitAssignmentResource extends JsonResource
{
    public function __construct(EmployeeUnitAssignmentData $resource)
    {
        parent::__construct($resource);
    }

    /** @return array<string, string|bool|null> */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
