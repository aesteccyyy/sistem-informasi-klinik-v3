<?php

namespace App\Filament\Pages;

class EmergencyCare extends InpatientCare
{
    protected static ?string $navigationIcon = 'heroicon-o-heart';
    protected static ?string $navigationLabel = 'IGD';
    protected static ?string $title = 'Instalasi Gawat Darurat';
    protected static ?int $navigationSort = 2;
    protected static string $careType = 'emergency';
    protected static string $featureColumn = 'has_emergency';
    protected static string $prefix = 'IGD';
}
