@extends('layouts.master')

@section('title', 'Employee QR Code')
@section('page-title', 'QR Code')

@section('body')
<body data-sidebar="colored">
@endsection

@section('content')
@php
    $qr = $employee->qrcode;
    $qrStatus = $qr?->status ?? 'none';
@endphp

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body text-center">

                <h4 class="mb-2">{{ $employee->user->full_name }}</h4>

                <p class="text-muted">
                    {{ $employee->company->name }} <br>
                    {{ $employee->employee_code }}
                </p>

                <hr>

                {{-- QR IMAGE --}}
                @if ($qr)
                    <div class="mb-3" id="qr-wrapper">
                        {!! QrCode::format('svg')->size(220)->generate($qr->qr_token) !!}
                    </div>

                    {{-- STATUS INDICATOR --}}
                    @switch($qrStatus)
                        @case('valid')
                            <span class="badge bg-success">VALID</span>
                            @break
                        @case('expired')
                            <span class="badge bg-warning">EXPIRED</span>
                            @break
                        @case('revoked')
                            <span class="badge bg-danger">REVOKED</span>
                            @break
                    @endswitch

                    {{-- DATES --}}
                    <div class="text-muted small mt-2">
                        <div>Created: {{ $qr->generated_at->format('M d, Y h:i A') }}</div>

                        @if ($qrStatus === 'valid')
                            <div>Expires: {{ $qr->expires_at->format('M d, Y h:i A') }}</div>
                        @endif
                    </div>

                @else
                    <span class="badge bg-secondary">NO QR GENERATED</span>
                @endif

                {{-- ACTION BUTTONS --}}
                <div class="d-flex justify-content-center gap-2 mt-4">

                    <form method="POST"
                          action="{{ route('admin.employees.qr.generate', $employee->id) }}">
                        @csrf
                        <button class="btn btn-primary">
                            Generate / Regenerate
                        </button>
                    </form>

                    @if ($qrStatus === 'valid')
                        <button onclick="downloadQr()" class="btn btn-outline-secondary">
                            Download
                        </button>

                        <button onclick="window.print()" class="btn btn-outline-secondary">
                            Print
                        </button>
                    @endif

                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ URL::asset('build/js/app.js') }}"></script>

<script>
function downloadQr() {
    const svg = document.querySelector('#qr-wrapper svg');
    if (!svg) return;

    const serializer = new XMLSerializer();
    const svgStr = serializer.serializeToString(svg);

    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    const img = new Image();
    const svgBlob = new Blob([svgStr], { type: 'image/svg+xml;charset=utf-8' });
    const url = URL.createObjectURL(svgBlob);

    img.onload = function () {
        canvas.width = img.width;
        canvas.height = img.height;
        ctx.drawImage(img, 0, 0);
        URL.revokeObjectURL(url);

        const pngUrl = canvas.toDataURL('image/png');
        const a = document.createElement('a');
        a.href = pngUrl;
        a.download = '{{ $employee->employee_code }}_QR.png';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    };

    img.src = url;
}
</script>
@endsection
