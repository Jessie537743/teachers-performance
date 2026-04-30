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

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Coerce arbitrary user input (legacy free-text, label, backing value, or
     * enum instance) into a valid case. Falls back to Faculty when nothing
     * matches — the safest default for an unknown personnel registration.
     */
    public static function coerce(mixed $input): self
    {
        if ($input instanceof self) {
            return $input;
        }

        if (! is_string($input)) {
            return self::Faculty;
        }

        $normalized = strtolower(trim($input));

        // Direct backing-value match (preferred path)
        foreach (self::cases() as $case) {
            if ($normalized === $case->value) {
                return $case;
            }
        }

        // Label match (e.g. "Dean / Head", "Program Chair")
        foreach (self::cases() as $case) {
            if ($normalized === strtolower($case->label())) {
                return $case;
            }
        }

        // Common legacy / colloquial strings
        return match (true) {
            str_contains($normalized, 'dean'),
            str_contains($normalized, 'department head'),
            str_contains($normalized, 'head'),               => self::DeanHead,
            str_contains($normalized, 'program chair'),
            str_contains($normalized, 'chair'),
            str_contains($normalized, 'coordinator'),         => self::ProgramChair,
            str_contains($normalized, 'staff'),
            str_contains($normalized, 'admin'),               => self::Staff,
            default                                            => self::Faculty,
        };
    }
}
