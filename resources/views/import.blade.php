@extends('layout')

@section('title', 'Upload Data - Monitoring Receiving Goods')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card filter-card">
                <div class="card-body p-4">
                    <h5 class="mb-1"><i class="bi bi-upload text-warning"></i> Upload Data Export</h5>
                    <p class="text-muted small mb-4">
                        Upload file hasil export "Monitoring Receiving Goods" (format .xls / .html / .htm).
                        Saat ini tersimpan <strong>{{ number_format($totalData) }}</strong> baris data di database.
                    </p>

                    <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih File</label>
                            <input type="file" name="file" class="form-control" accept=".xls,.html,.htm" required>
                            <div class="form-text">Maks. 50MB. File tidak perlu diubah formatnya, langsung upload hasil export.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold d-block">Mode Import</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="modeAppend" value="append" checked>
                                <label class="form-check-label" for="modeAppend">
                                    <strong>Tambahkan / Perbarui</strong> &mdash; data baru ditambahkan, baris dengan No. BSTHP
                                    &amp; Label Barcode yang sama akan diperbarui (tidak akan dobel).
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="modeReplace" value="replace">
                                <label class="form-check-label" for="modeReplace">
                                    <strong>Ganti Semua Data</strong> &mdash; seluruh data lama dihapus, diganti isi file ini.
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-cloud-upload-fill"></i> Proses Import
                        </button>
                    </form>
                </div>
            </div>

            @if ($lastImports->isNotEmpty())
                <div class="card table-card mt-3">
                    <div class="card-header bg-white fw-semibold small">Riwayat Import Terakhir</div>
                    <ul class="list-group list-group-flush">
                        @foreach ($lastImports as $batch)
                            <li class="list-group-item small text-muted">{{ $batch }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endsection
