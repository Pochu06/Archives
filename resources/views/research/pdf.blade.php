<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 1.5in 1in 0.75in 1in;
            size: letter;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* ── Fixed Header (repeats on all pages) ── */
        .page-header {
            position: fixed;
            top: -1.3in;
            left: -0.5in;
            right: -0.5in;
            text-align: center;
            padding: 8px 0 10px 0;
        }
        .page-header img {
            width: 80px;
            height: auto;
            display: block;
            margin: 0 auto 3px auto;
        }
        .page-header-text {
            font-size: 12pt;
            font-weight: bold;
            margin: 0;
        }

        /* ── Content wrapper ── */
        .page-content {
            margin-top: 0;
        }

        /* ── Title Block ── */
        .title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 24px;
            line-height: 1.4;
        }

        /* ── Authors & Affiliations ── */
        .authors-block {
            text-align: center;
            margin-bottom: 24px;
        }
        .author-name {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .author-affiliation {
            font-size: 10pt;
            font-style: italic;
            color: #333;
            margin-bottom: 2px;
        }

        /* ── Abstract Box ── */
        .abstract-box {
            margin: 20px 0 10px 0;
        }
        .abstract-label {
            font-weight: bold;
            font-size: 12pt;
            margin-bottom: 4px;
        }
        .abstract-text {
            font-style: italic;
            font-size: 12pt;
            text-align: justify;
            text-indent: 0.5in;
            line-height: 1.5;
            margin-top: 4px;
        }
        .keywords {
            font-size: 12pt;
            margin-top: 8px;
        }
        .keywords-label {
            font-weight: bold;
        }
        .keywords-text {
            font-style: italic;
        }

        /* ── Section Headings ── */
        .section-heading {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        .sub-heading {
            font-size: 12pt;
            font-weight: bold;
            margin-top: 18px;
            margin-bottom: 8px;
        }

        /* ── Body Content ── */
        .section-content {
            text-align: justify;
            text-indent: 0.5in;
            margin-bottom: 8px;
            line-height: 1.5;
        }

        /* ── References ── */
        .reference-item {
            text-indent: -0.5in;
            padding-left: 0.5in;
            margin-bottom: 10px;
            text-align: justify;
            font-size: 11pt;
            line-height: 1.4;
        }
        .ref-author {
            font-weight: bold;
        }

        /* ── Tables ── */
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 11pt;
        }
        .content-table th,
        .content-table td {
            border: 1px solid #000;
            padding: 6px 10px;
            text-align: left;
            vertical-align: top;
        }
        .content-table th {
            font-weight: bold;
            background-color: #f0f0f0;
            text-align: center;
        }
        .content-table td {
            text-indent: 0;
        }
        .table-caption {
            font-style: italic;
            font-size: 11pt;
            margin-bottom: 5px;
            margin-top: 15px;
        }

        /* ── Figures ── */
        .figure-container {
            text-align: center;
            margin: 20px 0;
        }
        .figure-image {
            max-width: 85%;
            margin: 0 auto;
            display: block;
        }
        .figure-caption {
            font-style: italic;
            font-size: 11pt;
            text-align: center;
            margin-top: 8px;
            text-indent: 0;
        }

    </style>
</head>
<body>

    {{-- ══ Fixed Header (repeats on all pages) ══ --}}
    <div class="page-header">
        <img src="{{ public_path('storage/logo/CSU.png') }}" alt="CSU Logo">
        <div class="page-header-text">CAGAYAN STATE UNIVERSITY</div>
    </div>

    {{-- ══ Title ══ --}}
    <div class="page-content">
    <div class="title">
        {{ strtoupper($research->title) }}
    </div>

    {{-- ══ Authors & Affiliations ══ --}}
    <div class="authors-block">
        @php $authors = array_map('trim', explode(',', $research->authors)); @endphp
        @foreach($authors as $i => $author)
            <div class="author-name"><sup>{{ $i + 1 }}</sup>{{ strtoupper($author) }}</div>
        @endforeach
        @foreach($authors as $i => $author)
            <div class="author-affiliation"><sup>{{ $i + 1 }}</sup>{{ $research->college->name ?? '' }}</div>
        @endforeach
    </div>

    {{-- ══ Abstract Box ══ --}}
    <div class="abstract-box">
        <div class="abstract-label">Abstract:</div>
        <div class="abstract-text">{{ $research->abstract }}</div>

        @if($research->keywords)
        <div class="keywords">
            <span class="keywords-label">Keywords:</span>
            <span class="keywords-text">{{ $research->keywords }}</span>
        </div>
        @endif
    </div>

    @php use App\Helpers\ContentHelper; @endphp

    {{-- ══ INTRODUCTION ══ --}}
    @if($research->introduction)
    <div class="section-heading">INTRODUCTION</div>
    {!! ContentHelper::renderContent($research->introduction, 'content-table', 'pdf') !!}
    @endif

    {{-- ══ METHODOLOGY ══ --}}
    @if($research->methodology)
    <div class="section-heading">METHODOLOGY</div>
    {!! ContentHelper::renderContent($research->methodology, 'content-table', 'pdf') !!}
    @endif

    {{-- ══ RESULTS AND DISCUSSION ══ --}}
    @if($research->results || $research->discussion)
    <div class="section-heading">RESULTS AND DISCUSSION</div>
    @if($research->results)
    {!! ContentHelper::renderContent($research->results, 'content-table', 'pdf') !!}
    @endif
    @if($research->discussion)
    {!! ContentHelper::renderContent($research->discussion, 'content-table', 'pdf') !!}
    @endif
    @endif

    {{-- ══ CONCLUSION AND RECOMMENDATIONS ══ --}}
    @if($research->conclusion || $research->recommendations)
    <div class="section-heading">CONCLUSION AND RECOMMENDATIONS</div>
    @if($research->conclusion)
    <div class="sub-heading">Conclusion</div>
    {!! ContentHelper::renderContent($research->conclusion, 'content-table', 'pdf') !!}
    @endif
    @if($research->recommendations)
    <div class="sub-heading">Recommendations</div>
    {!! ContentHelper::renderContent($research->recommendations, 'content-table', 'pdf') !!}
    @endif
    @endif

    {{-- ══ REFERENCES ══ --}}
    @if($research->references)
    <div class="section-heading">REFERENCES</div>
    @foreach(preg_split('/\r?\n/', $research->references) as $ref)
        @if(trim($ref))
        @php
            $refText = trim($ref);
            // Bold the author portion (everything before the year in parentheses)
            $formatted = preg_replace('/^(.+?\.\s*\()/', '<span class="ref-author">$1</span>', $refText, 1);
            // Close the bold at the closing paren if we matched
            if ($formatted === $refText) {
                // No match — try alternate: bold up to first period
                $formatted = preg_replace('/^([^.]+\.)/', '<span class="ref-author">$1</span>', $refText, 1);
            }
        @endphp
        <div class="reference-item">{!! $formatted !!}</div>
        @endif
    @endforeach
    @endif

    </div>{{-- end .page-content --}}

</body>
</html>
