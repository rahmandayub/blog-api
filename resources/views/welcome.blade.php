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
            <svg class="w-full h-auto max-w-xs text-indigo-400" viewBox="0 0 100 100" fill="currentColor">
                <path
                    d="M72.2,58.8c-6.3-1.2-11.8-5.3-15.2-10.7c-2.4-3.8-3.5-8.2-3.3-12.7c0.2-5.6,2.2-10.9,5.7-15.1c3.4-4,8-6.8,13-8.1 c10.3-2.6,21.3,2,26.8,11.3c4.4,7.4,5.3,16.5,2.4,24.5C98.6,56.7,91.2,62,82.2,63C78.9,63.4,75.5,62.1,72.2,58.8z M80.4,14.1 c-4.1,1-7.7,3.3-10.4,6.4c-2.9,3.3-4.5,7.6-4.7,12.1c-0.2,4.8,1,9.4,3.4,13.4c2.8,4.5,7.1,7.9,12.2,8.8c5.9,1.1,11.9-1,16-5.4 c4.5-4.8,6-11.4,4.2-17.6C98.4,25.9,91.3,18,82.5,15.1C81.8,14.9,81.1,14.5,80.4,14.1z">
                </path>
                <path
                    d="M48.4,85.9H19.6c-4.4,0-8-3.6-8-8V26.1c0-4.4,3.6-8,8-8h28.7c2.2,0,4,1.8,4,4s-1.8,4-4,4H19.6c0,0,0,0,0,0v51.8 c0,0,0,0,0,0h28.7c2.2,0,4,1.8,4,4S50.6,85.9,48.4,85.9z">
                </path>
                <path d="M43.3,42.2H23.6c-2.2,0-4-1.8-4-4s1.8-4,4-4h19.7c2.2,0,4,1.8,4,4S45.5,42.2,43.3,42.2z"></path>
                <path d="M34.3,58.2H23.6c-2.2,0-4-1.8-4-4s1.8-4,4-4h10.7c2.2,0,4,1.8,4,4S36.5,58.2,34.3,58.2z"></path>
            </svg>
        </div>

    </div>

</body>

</html>
