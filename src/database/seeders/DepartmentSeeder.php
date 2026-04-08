<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['code' => 'CCIS',      'name' => 'College of Computing and Information Sciences',    'department_type' => 'teaching'],
            ['code' => 'CTE',       'name' => 'College of Teacher Education',                     'department_type' => 'teaching'],
            ['code' => 'CCJE',      'name' => 'College of Criminal Justice Education',            'department_type' => 'teaching'],
            ['code' => 'CAS',       'name' => 'College of Arts and Sciences',                     'department_type' => 'teaching'],
            ['code' => 'CBMA',      'name' => 'College of Business and Management',               'department_type' => 'teaching'],
            ['code' => 'CTHM',      'name' => 'College of Tourism and Hospitality Management',    'department_type' => 'teaching'],
            ['code' => 'EDP',       'name' => 'Electronic Data Processing',                       'department_type' => 'non-teaching'],
            ['code' => 'PMO',       'name' => 'Project Management Office',                        'department_type' => 'non-teaching'],
            ['code' => 'Registrar', 'name' => 'Office of the Registrar',                         'department_type' => 'non-teaching'],
            ['code' => 'Finance',   'name' => 'Office of the Finance',                            'department_type' => 'non-teaching'],
            ['code' => 'OSAS',      'name' => 'Office of Student Affairs and Services',           'department_type' => 'non-teaching'],
            ['code' => 'RIID',      'name' => 'Research Instructional and Innovation Department', 'department_type' => 'non-teaching'],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }
    }
}
