<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class GenerateApplicationManual extends Command
{
    protected $signature = 'docs:generate-manual';

    protected $description = 'Generate the SIMKLINIK application manual as a PDF';

    public function handle(): int
    {
        $path = base_path('docs/Panduan_Pengguna_SIMKLINIK.pdf');

        Pdf::loadView('docs.manual', ['generatedAt' => now()->locale('id')->translatedFormat('d F Y')])
            ->setPaper('a4')
            ->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans',
            ])
            ->save($path);

        $this->info("Panduan berhasil dibuat: {$path}");

        return self::SUCCESS;
    }
}
