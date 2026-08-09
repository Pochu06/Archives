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

        .signatures {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 54px;
            width: 100%;
        }

        .signature-box {
            width: 40%;
            display: inline-block;
            text-align: center;
            vertical-align: top;
        }

        .line {
            border-top: 1px solid #374151;
            margin: 0 auto 6px;
            width: 100%;
        }

        .sign-name {
            font-size: 12px;
            font-weight: 700;
            color: #111827;
        }

        .sign-role {
            font-size: 10px;
            color: #6b7280;
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
    @endphp

    <div class="certificate">
        <div class="content">
            <p class="org">Cagayan State University - ARCHIVES</p>
            <h1 class="title">Certificate of Approval</h1>
            <p class="subtitle">Research and Development Extension Office</p>

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

            <div class="signatures">
                <div class="signature-box" style="margin-right: 8%;">
                    <div class="line"></div>
                    <p class="sign-name">{{ $research->approver->name ?? 'RDE Approving Officer' }}</p>
                    <p class="sign-role">RDE Approving Officer</p>
                </div>
                <div class="signature-box">
                    <div class="line"></div>
                    <p class="sign-name">{{ $research->user->name ?? 'Research Author' }}</p>
                    <p class="sign-role">Research Author</p>
                </div>
            </div>

            <p class="footer">Generated on {{ now()->format('F d, Y h:i A') }} | ARCHIVES Research Repository</p>
        </div>
    </div>
</body>
</html>
