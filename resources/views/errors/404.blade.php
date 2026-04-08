<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 - Page Not Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-orange-50 flex items-center justify-center min-h-screen">
    <div class="text-center">
        <div class="text-9xl font-extrabold text-orange-500 mb-4">404</div>
        <h1 class="text-3xl font-bold text-gray-800 mb-3">Page Not Found</h1>
        <p class="text-gray-600 mb-8 max-w-sm">The page you are looking for doesn't exist or has been moved.</p>
        <a href="{{ url('/') }}" class="bg-gradient-to-r from-orange-600 to-orange-700 text-white px-8 py-3 rounded-xl font-bold hover:from-orange-700 hover:to-orange-800 transition">
            <i class="fas fa-home mr-2"></i> Go Home
        </a>
    </div>
</body>
</html>
