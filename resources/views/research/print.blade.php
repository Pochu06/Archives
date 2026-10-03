<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Research Report</title>
    @php
        $yearFrom = request('year');
        $yearTo = request('year_to');
        $isRange = $yearFrom && $yearTo && (string) $yearFrom !== (string) $yearTo;
        $singleYear = $isRange ? null : ($yearFrom ?: $yearTo);
        $selectedCollege = request('college_id') ? $colleges->firstWhere('id', (int) request('college_id')) : null;
        $showYearCol = $isRange;
        $showCollegeCol = ! $selectedCollege;
        $yearLabel = $isRange ? min($yearFrom, $yearTo) . '–' . max($yearFrom, $yearTo) : ($singleYear ?: 'All Years');
        $collegeLabel = $selectedCollege ? $selectedCollege->name . ' (' . $selectedCollege->code . ')' : 'All Colleges';
        $colCount = 4 + ($showYearCol ? 1 : 0) + ($showCollegeCol ? 1 : 0);
    @endphp
    <style>
        body { margin: 0; padding: 24px; background: #fff; }
        .report-sheet { font-family: 'Times New Roman', Times, serif; color: #111; max-width: 960px; margin: 0 auto; }
        .rpt-head { display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; border-bottom: 2px solid #111; padding-bottom: 10px; margin-bottom: 18px; }
        .rpt-head img { width: 80px; height: 80px; object-fit: contain; }
        .rpt-head .l1 { font-size: 14px; }
        .rpt-head .l2 { font-size: 22px; font-weight: bold; text-transform: uppercase; }
        .rpt-head .l3 { font-size: 16px; font-weight: bold; }
        .rpt-head .l4 { font-size: 12px; }
        .rpt-title { text-align: center; margin-bottom: 14px; }
        .rpt-title h2 { font-size: 18px; font-weight: bold; margin: 0; }
        .rpt-title p { font-size: 13px; margin: 2px 0 0; }
        .rpt-meta td { padding: 2px 16px 2px 0; font-size: 13px; }
        .rpt-meta td:first-child { font-weight: bold; white-space: nowrap; }
        table.rpt-table { width: 100%; border-collapse: collapse; table-layout: auto; margin-top: 16px; }
        table.rpt-table th, table.rpt-table td { border: 1px solid #111; padding: 6px 8px; font-size: 12px; vertical-align: top; text-align: left; }
        table.rpt-table th { background: #f2f2f2; text-align: center; text-transform: uppercase; }
        table.rpt-table td.c { text-align: center; white-space: nowrap; }
        table.rpt-table tr { page-break-inside: avoid; }
        .rpt-foot { text-align: center; font-size: 11px; margin-top: 30px; }
        @media print {
            @page { size: 8.5in 13in; margin: 15mm; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="report-sheet">
        <div class="rpt-head">
            <img src="{{ asset('storage/logo/CSU.png') }}" alt="CSU Logo">
            <div>
            <div class="l1">Republic of the Philippines</div>
            <div class="l2">Cagayan State University</div>
            <div class="l3">Aparri Campus</div>
            <div class="l4">Aparri, Cagayan</div>
            <div class="l2">Research, Development and Extension Office</div>
            </div>
        </div>

        <div class="rpt-title">
            <h2>RESEARCH REPORT</h2>
            <p>List of Research Studies</p>
        </div>

        <table class="rpt-meta">
            <tr><td>Year:</td><td>{{ $yearLabel }}</td></tr>
            <tr><td>College:</td><td>{{ $collegeLabel }}</td></tr>
            <tr><td>Report Date:</td><td>{{ now()->format('F j, Y') }}</td></tr>
        </table>

        <table class="rpt-table">
            <thead>
                <tr>
                    <th style="width:6%">No.</th>
                    <th>Title of Research</th>
                    <th>Researchers / Authors</th>
                    <th>Category</th>
                    @if($showYearCol)<th style="width:9%">Year</th>@endif
                    @if($showCollegeCol)<th style="width:12%">College</th>@endif
                    <th>Year</th>
                </tr>
            </thead>
            <tbody>
                @forelse($research as $r)
                <tr>
                    <td class="c">{{ $loop->iteration }}</td>
                    <td>{{ $r->title }}</td>
                    <td>{{ $r->authors }}</td>
                    <td>{{ $r->category->name ?? 'N/A' }}</td>
                    @if($showYearCol)<td class="c">{{ $r->publication_year }}</td>@endif
                    @if($showCollegeCol)<td class="c">{{ $r->college->code ?? 'N/A' }}</td>@endif
                    <td class="c">{{ $r->publication_year }}</td>
                </tr>
                @empty
                <tr><td colspan="{{ $colCount }}" style="text-align:center;padding:30px">No research papers match your current filters.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="rpt-foot">This is a system-generated report. No signature is required.<br>Generated from ARCHIVES on {{ now()->format('F j, Y g:i A') }}.</div>
    </div>
    <script>window.addEventListener('load', function () { window.print(); });</script>
</body>
</html>

