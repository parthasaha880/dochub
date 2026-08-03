<?php

namespace App\Modules\Archive\Enums;

enum LocationType: string
{
    case Room = 'room';
    case Rack = 'rack';
    case Shelf = 'shelf';
    case Box = 'box';
    case File = 'file';

    public function label(): string
    {
        return match ($this) {
            self::Room => 'Room',
            self::Rack => 'Rack',
            self::Shelf => 'Shelf',
            self::Box => 'Box',
            self::File => 'File',
        };
    }

    public function childType(): ?self
    {
        return match ($this) {
            self::Room => self::Rack,
            self::Rack => self::Shelf,
            self::Shelf => self::Box,
            self::Box => self::File,
            self::File => null,
        };
    }

    public function parentType(): ?self
    {
        return match ($this) {
            self::Room => null,
            self::Rack => self::Room,
            self::Shelf => self::Rack,
            self::Box => self::Shelf,
            self::File => self::Box,
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
