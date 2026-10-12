<?php

use App\Enums\RoleEnum;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/login')->name('home');

Route::get('/transactions/{transaction}/receipt', function (Transaction $transaction) {
	$user = auth()->user();

	if (!$user->hasRole(RoleEnum::SUPER_ADMIN->value) && !$user->hasRole(RoleEnum::ADMIN->value)) {
		$clinicId = $transaction->appointment?->clinic_user?->clinic_id;
		abort_unless(
			$clinicId && $user->clinics()->whereKey($clinicId)->exists(),
			403,
		);
	}

	$transaction->loadMissing(['bills', 'appointment.clinic_user.clinic']);
	$clinic = $transaction->appointment?->clinic_user?->clinic;
	$logoData = null;

	if ($clinic?->logo && Storage::disk('public')->exists($clinic->logo)) {
		$mimeType = Storage::disk('public')->mimeType($clinic->logo);
		$logoData = 'data:' . $mimeType . ';base64,' . base64_encode(
			Storage::disk('public')->get($clinic->logo)
		);
	}

	return Pdf::loadView('pdf', [
		'record' => $transaction,
		'clinic' => $clinic,
		'logoData' => $logoData,
	])->download('invoice-' . $transaction->id . '.pdf');
})->middleware('auth')->name('transactions.receipt');
