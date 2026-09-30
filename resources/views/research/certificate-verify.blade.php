<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification - ARCHIVES</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <main class="mx-auto max-w-3xl px-4 py-10 sm:py-16">
        <div class="overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-slate-200">
            <div class="bg-blue-700 px-6 py-8 text-center text-white sm:px-10">
                <img src="{{ asset('storage/logo/CSU.png') }}" alt="Cagayan State University logo" class="mx-auto mb-4 h-20 w-20 object-contain">
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-blue-100">Cagayan State University</p>
                <h1 class="mt-2 text-3xl font-extrabold">Certificate Verification</h1>
            </div>

            <div class="p-6 sm:p-10">
                @if($isLegitimate)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-600 text-2xl text-white">✓</div>
                    <h2 class="mt-3 text-xl font-bold text-emerald-900">Legitimate Certificate</h2>
                    <p class="mt-1 text-sm text-emerald-800">This certificate matches an RDE-approved research paper in the ARCHIVES repository.</p>
                </div>
                @else
                <div class="rounded-2xl border border-red-200 bg-red-50 p-5 text-center">
                    <h2 class="text-xl font-bold text-red-900">Certificate Not Valid</h2>
                    <p class="mt-1 text-sm text-red-800">This paper does not have a valid final RDE approval record.</p>
                </div>
                @endif

                <section class="mt-8">
                    <h2 class="text-lg font-bold text-slate-900">Research Record</h2>
                    <dl class="mt-4 divide-y divide-slate-200 rounded-2xl border border-slate-200">
                        <div class="grid gap-1 px-4 py-3 sm:grid-cols-3"><dt class="text-sm font-semibold text-slate-500">Paper</dt><dd class="text-sm sm:col-span-2">{{ $research->title }}</dd></div>
                        <div class="grid gap-1 px-4 py-3 sm:grid-cols-3"><dt class="text-sm font-semibold text-slate-500">Author(s)</dt><dd class="text-sm sm:col-span-2">{{ $research->authors ?: ($research->user->name ?? 'N/A') }}</dd></div>
                        <div class="grid gap-1 px-4 py-3 sm:grid-cols-3"><dt class="text-sm font-semibold text-slate-500">College</dt><dd class="text-sm sm:col-span-2">{{ $research->college->name ?? 'N/A' }}</dd></div>
                        <div class="grid gap-1 px-4 py-3 sm:grid-cols-3"><dt class="text-sm font-semibold text-slate-500">Submitted</dt><dd class="text-sm sm:col-span-2">{{ $research->created_at?->format('F d, Y h:i A') ?? 'N/A' }}</dd></div>
                        <div class="grid gap-1 px-4 py-3 sm:grid-cols-3"><dt class="text-sm font-semibold text-slate-500">RDE Approved</dt><dd class="text-sm sm:col-span-2">{{ $research->approved_at?->format('F d, Y h:i A') ?? 'Not approved' }}</dd></div>
                        <div class="grid gap-1 px-4 py-3 sm:grid-cols-3"><dt class="text-sm font-semibold text-slate-500">Approving Office</dt><dd class="text-sm sm:col-span-2">Research and Development Extension Office</dd></div>
                    </dl>
                </section>

                <section class="mt-8 rounded-2xl border border-blue-200 bg-blue-50 p-5">
                    <h2 class="text-lg font-bold text-blue-950">Verification Receipt</h2>
                    <dl class="mt-3 space-y-2 text-sm text-blue-900">
                        <div class="flex justify-between gap-4"><dt class="font-semibold">Certificate reference</dt><dd class="font-mono text-right">{{ $certificateReference }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="font-semibold">Verification status</dt><dd>{{ $isLegitimate ? 'Verified legitimate' : 'Not verified' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="font-semibold">Verified at</dt><dd class="text-right">{{ now()->format('F d, Y h:i A') }}</dd></div>
                    </dl>
                    <p class="mt-4 text-xs text-blue-800">Keep this receipt with the certificate as proof that the QR code was checked against the official ARCHIVES record.</p>
                </section>

                <div class="mt-8 text-center">
                    <a href="{{ route('research.public-show', $research->id) }}" class="font-semibold text-blue-700 hover:underline">View public research record</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>