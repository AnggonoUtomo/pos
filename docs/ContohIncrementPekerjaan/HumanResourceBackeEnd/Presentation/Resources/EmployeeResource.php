<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Presentation\Resources;

use App\Modules\HumanResource\HumanResource\Application\DTO\EmployeeData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EmployeeData */
final class EmployeeResource extends JsonResource
{
    public function __construct(EmployeeData $resource)
    {
        parent::__construct($resource);
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return $this->resource->toArray();
    }
}
