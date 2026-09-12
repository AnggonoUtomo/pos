<?php

declare(strict_types=1);

namespace App\Modules\HumanResource\HumanResource\Database\Seeders;

use App\Modules\HumanResource\HumanResource\Infrastructure\Models\EmployeeRecord;
use App\Modules\HumanResource\HumanResource\Infrastructure\Models\EmployeeUnitAssignmentRecord;
use App\Modules\Organization\Organization\Infrastructure\Models\OrganizationUnitRecord;
use Illuminate\Database\Seeder;

final class HumanResourceDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (config('app.env') === 'production') {
            return;
        }

        $pesantren = OrganizationUnitRecord::where('code', 'DEMO-PESANTREN')->first();
        $mts = OrganizationUnitRecord::where('code', 'DEMO-MTS')->first();
        $ma = OrganizationUnitRecord::where('code', 'DEMO-MA')->first();
        $asramaPutra = OrganizationUnitRecord::where('code', 'DEMO-ASRAMA-PUTRA')->first();
        $asramaPutri = OrganizationUnitRecord::where('code', 'DEMO-ASRAMA-PUTRI')->first();

        if (! $pesantren || ! $mts || ! $ma || ! $asramaPutra || ! $asramaPutri) {
            return;
        }

        $employees = [
            ['PEG-DEMO-001', 'KH Ahmad Fikri', 'Kiai', 'unit_head', 'Pengasuh Pesantren', 'active', '2020-07-01', null, $pesantren->id, 'demo_pengasuh'],
            ['PEG-DEMO-002', 'Ustazah Nur Aini', 'Bu Nur', 'musyrif', 'Kepala Asrama Putri', 'active', '2021-07-01', null, $asramaPutri->id, 'demo_pembina_asrama'],
            ['PEG-DEMO-003', 'Ustaz Budi Santoso', 'Pak Budi', 'teacher', 'Guru Tahfidz Demo', 'active', '2022-07-01', null, $mts->id, 'demo_guru'],
            ['PEG-DEMO-004', 'Ustazah Salma Zahra', 'Bu Salma', 'teacher', 'Guru Bahasa Arab Demo', 'active', '2023-07-01', null, $ma->id, 'demo_guru'],
            ['PEG-DEMO-005', 'Pak Rahmat Hidayat', 'Pak Rahmat', 'administration_staff', 'Petugas Administrasi', 'inactive', '2021-01-01', '2026-06-30', $pesantren->id, 'demo_administrasi'],
            ['PEG-DEMO-006', 'Bu Lilis Kurnia', 'Bu Lilis', 'musyrif', 'Pembina Asrama Putra', 'inactive', '2021-07-01', '2026-06-30', $asramaPutra->id, 'demo_pembina_asrama'],
        ];

        foreach ($employees as [$employeeNo, $name, $preferredName, $employmentType, $position, $status, $joinedOn, $leftOn, $unitId, $role]) {
            $employee = EmployeeRecord::firstOrNew(['employee_no' => $employeeNo]);
            $employee->fill([
                'primary_unit_id' => $unitId,
                'name' => $name,
                'preferred_name' => $preferredName,
                'employment_type' => $employmentType,
                'position' => $position,
                'status' => $status,
                'joined_on' => $joinedOn,
                'left_on' => $leftOn,
                'notes' => 'Data demo lifecycle SDM.',
            ]);
            $employee->save();

            EmployeeUnitAssignmentRecord::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'organization_unit_id' => $unitId,
                    'role' => $role,
                ],
                [
                    'starts_on' => $joinedOn,
                    'ends_on' => $leftOn,
                    'is_primary' => true,
                ],
            );
        }
    }
}
