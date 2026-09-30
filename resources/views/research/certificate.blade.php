<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Research Approval</title>
    <style>
        @page {
            size: letter landscape;
            margin: 20px;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            background: #ffffff;
        }

        .certificate {
            position: relative;
            border: 8px solid #d97706;
            padding: 16px;
            height: 548px;
            box-sizing: border-box;
            overflow: hidden;
        }

        .certificate::before {
            content: '';
            position: absolute;
            top: 14px;
            left: 14px;
            right: 14px;
            bottom: 14px;
            border: 2px solid #f59e0b;
        }

        .content {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 8px 22px 150px;
        }

        .watermark {
            position: absolute;
            top: 210px;
            left: 0;
            right: 0;
            z-index: 1;
            text-align: center;
            font-size: 54px;
            font-weight: 800;
            letter-spacing: 3px;
            color: #d1d5db;
            opacity: 0.28;
            transform: rotate(-25deg);
        }

        .logo {
            width: 62px;
            height: 62px;
            object-fit: contain;
            margin-bottom: 4px;
        }

        .org {
            font-size: 13px;
            letter-spacing: 1px;
            color: #92400e;
            font-weight: 700;
            text-transform: uppercase;
        }

        .title {
            font-size: 36px;
            font-weight: 800;
            letter-spacing: 1px;
            margin-top: 12px;
            margin-bottom: 4px;
            color: #111827;
        }

        .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 16px;
        }

        .recipient {
            font-size: 23px;
            font-weight: 700;
            margin: 12px 0 8px;
            line-height: 1.25;
            color: #0f172a;
        }

        .statement {
            font-size: 13px;
            line-height: 1.5;
            color: #374151;
            margin: 0 auto;
            max-width: 860px;
        }

        .paper-title {
            margin: 10px auto 8px;
            max-width: 900px;
            font-size: 16px;
            line-height: 1.4;
            font-weight: 700;
            color: #b45309;
        }

        .meta {
            margin-top: 12px;
            font-size: 11px;
            color: #4b5563;
        }

        .system-generated {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 56px;
            text-align: center;
            font-weight: 700;
            font-size: 10px;
            color: #92400e;
            letter-spacing: 0.4px;
        }

        .footer {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 20px;
            margin: 0;
            font-size: 9px;
            color: #6b7280;
        }

        .badge {
            margin: 10px auto 0;
            display: inline-block;
            padding: 5px 10px;
            border: 1px solid #f59e0b;
            border-radius: 16px;
            font-size: 10px;
            font-weight: 700;
            color: #92400e;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            background: #fffbeb;
        }

        .qr-code {
            position: absolute;
            right: 28px;
            bottom: 20px;
            z-index: 3;
            width: 68px;
            height: 68px;
        }

        .qr-label {
            position: absolute;
            right: 20px;
            bottom: 2px;
            z-index: 3;
            font-size: 7px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    @php
        $authorNames = collect(explode(',', (string) $research->authors))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->implode(', ');

        $displayAuthors = \Illuminate\Support\Str::limit(
            $authorNames !== '' ? $authorNames : ($research->user->name ?? 'Research Author'),
            95,
            '...'
        );

        $displayTitle = \Illuminate\Support\Str::limit((string) $research->title, 170, '...');

        $approvedAt = $research->approved_at ? $research->approved_at->format('F d, Y') : now()->format('F d, Y');
        $logoPath = public_path('storage/logo/CSU.png');
        $logoData = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
        $qrCode = (new \Endroid\QrCode\Builder\Builder(
            writer: new \Endroid\QrCode\Writer\PngWriter(),
            data: route('research.certificate.verify', $research->id),
            size: 180,
            margin: 8,
        ))->build()->getDataUri();
    @endphp

    <div class="certificate">
        <div class="watermark">ARCHIVES</div>
        <div class="content">
            @if($logoData)
            <img class="logo" src="{{ $logoData }}" alt="Cagayan State University logo">
            @endif
            <p class="org">Cagayan State University - ARCHIVES</p>
            <h1 class="title">Certificate of Approval</h1>
            <p class="subtitle">Research, Development, and Extension Office</p>

            <p class="statement">This certifies that the research paper authored by</p>
            <p class="recipient">{{ $displayAuthors }}</p>
            <p class="statement">has successfully passed final review and has been approved for official archiving in the institutional research repository.</p>

            <p class="paper-title">"{{ $displayTitle }}"</p>

            <p class="meta">
                College: {{ $research->college->name ?? 'N/A' }}
                &nbsp;|&nbsp;
                Category: {{ $research->category->name ?? 'N/A' }}
                &nbsp;|&nbsp;
                Publication Year: {{ $research->publication_year }}
            </p>

            <span class="badge">RDE Approved on {{ $approvedAt }}</span>

            <p class="system-generated">* This is a system-generated certificate. No physical signature is required. Scan the QR code to verify authenticity.</p>

            <p class="footer">Generated on {{ now()->format('F d, Y h:i A') }} | ARCHIVES Research Repository</p>
        </div>
        <img class="qr-code" src="{{ $qrCode }}" alt="QR code for this research paper">
    </div>
</body>
</html>
