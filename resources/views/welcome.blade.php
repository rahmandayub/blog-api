<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Blog Panel</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
        }

        /* A simple fade-in animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }
    </style>
</head>

<body
    class="flex items-center justify-center min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-indigo-900 text-gray-900 antialiased p-4">

    <div
        class="animate-fade-in bg-white rounded-2xl shadow-2xl max-w-4xl w-full grid grid-cols-1 md:grid-cols-2 overflow-hidden">

        <div class="p-8 sm:p-12 flex flex-col justify-center">
            <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-800 mb-4">Admin Panel</h1>
            <p class="text-base sm:text-lg text-gray-600 mb-8">Welcome! Manage your articles, content, and users with
                ease.</p>

            <div class="mb-8 space-y-4">
                <div class="flex items-center">
                    <svg class="h-6 w-6 text-indigo-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span class="text-gray-700">Create & Edit Posts</span>
                </div>
            </div>

            <a href="/admin/login"
                class="w-full text-center px-8 py-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg shadow-lg transition duration-300 ease-in-out transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-75">
                Login to Continue
            </a>
        </div>

        <div class="hidden md:flex items-center justify-center p-8 bg-indigo-50">
            <i class="fa-solid fa-file-pen text-indigo-500 text-[16rem]"></i>
        </div>

    </div>

</body>

</html>
