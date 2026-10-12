<!DOCTYPE html>
<html lang="id">
<head>
	<meta charset="utf-8">
	<title>Struk Pembayaran #{{ $record->id }}</title>
	<style>
		body { color: #202a31; font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 32px; }
		.header { border-bottom: 2px solid #176b5b; margin-bottom: 22px; padding-bottom: 16px; }
		.brand { display: inline-block; vertical-align: middle; }
		.logo { height: 64px; margin-right: 14px; max-width: 100px; object-fit: contain; vertical-align: middle; }
		h1 { color: #176b5b; font-size: 20px; margin: 0 0 5px; }
		h2 { font-size: 15px; margin: 22px 0 10px; }
		.muted { color: #65727a; }
		.details { margin: 16px 0; width: 100%; }
		.details td { padding: 4px 0; vertical-align: top; width: 50%; }
		table.items { border-collapse: collapse; margin-top: 10px; width: 100%; }
		.items th, .items td { border-bottom: 1px solid #dce3e5; padding: 9px 7px; text-align: left; }
		.items th { background: #edf4f2; color: #29443e; }
		.number { text-align: right !important; white-space: nowrap; }
		.total { border-top: 2px solid #176b5b; font-size: 14px; font-weight: bold; margin-top: 12px; padding-top: 12px; text-align: right; }
		.status { color: #176b5b; font-weight: bold; text-transform: uppercase; }
		.footer { border-top: 1px solid #dce3e5; color: #65727a; margin-top: 32px; padding-top: 12px; text-align: center; }
	</style>
</head>
<body>
	<div class="header">
		@if ($logoData)
			<img class="logo" src="{{ $logoData }}" alt="Logo klinik">
		@endif
		<div class="brand">
			<h1>{{ $clinic?->name ?? 'Klinik' }}</h1>
			<div>{{ $clinic?->address ?? 'Alamat klinik tidak tersedia' }}</div>
		</div>
	</div>

	<h2>Struk Pembayaran</h2>
	<table class="details">
		<tr>
			<td><span class="muted">No. Transaksi</span><br>#{{ $record->id }}</td>
			<td><span class="muted">Tanggal</span><br>{{ $record->appointment_date?->format('d/m/Y') ?? '-' }}</td>
		</tr>
		<tr>
			<td><span class="muted">Pasien</span><br>{{ $record->patient_name }}</td>
			<td><span class="muted">Dokter</span><br>{{ $record->doctor_name }}</td>
		</tr>
		<tr>
			<td><span class="muted">Telepon</span><br>{{ $record->patient_phone ?? '-' }}</td>
			<td><span class="muted">Status pembayaran</span><br><span class="status">{{ $record->status->label() }}</span></td>
		</tr>
	</table>

	<h2>Rincian Tagihan</h2>
	<table class="items">
		<thead>
			<tr>
				<th>Item</th>
				<th>Jenis</th>
				<th class="number">Qty</th>
				<th class="number">Harga</th>
				<th class="number">Diskon</th>
				<th class="number">Jumlah</th>
			</tr>
		</thead>
		<tbody>
			@forelse ($record->bills as $bill)
				<tr>
					<td>{{ $bill->item_name }}@if ($bill->item_description)<br><span class="muted">{{ $bill->item_description }}</span>@endif</td>
					<td>{{ $bill->type->label() }}</td>
					<td class="number">{{ $bill->quantity }}</td>
					<td class="number">Rp {{ number_format($bill->price, 0, ',', '.') }}</td>
					<td class="number">Rp {{ number_format($bill->discount, 0, ',', '.') }}</td>
					<td class="number">Rp {{ number_format($bill->amount, 0, ',', '.') }}</td>
				</tr>
			@empty
				<tr><td colspan="6">Tidak ada rincian tagihan.</td></tr>
			@endforelse
		</tbody>
	</table>

	<div class="total">Total: Rp {{ number_format($record->amount_total, 0, ',', '.') }}</div>
	<div class="muted" style="margin-top: 8px; text-align: right;">Dibayar pada: {{ $record->paid_at?->format('d/m/Y H:i') ?? '-' }}</div>
	<div class="footer">Terima kasih telah menggunakan layanan {{ $clinic?->name ?? 'klinik kami' }}.</div>
</body>
</html>
