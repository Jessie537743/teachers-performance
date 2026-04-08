<?php

namespace App\Enums;

enum FacultyDepartmentPosition: string
{
    case DeanHead = 'dean_head';
    case ProgramChair = 'program_chair';
    case Faculty = 'faculty';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::DeanHead => 'Dean / Head',
            self::ProgramChair => 'Program Chair',
            self::Faculty => 'Faculty',
            self::Staff => 'Staff',
        };
    }
}
