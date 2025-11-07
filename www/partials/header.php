<!DOCTYPE html>
<html lang="en">
<head>
<title>oEmbed</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="The oEmbed spec">
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
<link rel="stylesheet" href="/vendor/fontawesome/css/all.min.css">
<link rel="stylesheet" href="/css/oembed.css">
</head>
<body class="bg-gray-900">

<nav class="fixed top-0 left-0 right-0 z-50 bg-gray-900/80 backdrop-blur-md shadow-lg">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="/" class="text-2xl text-white font-bold flex items-center">oEmbed</a>
            <div class="hidden md:flex items-center space-x-1 text-gray-300">
                <a href="/#section1" class="nav-link px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 ease-in-out hover:bg-gray-700 hover:text-white flex items-center"><i class="fas fa-bolt mr-2"></i>Example</a>
                <a href="/#section2" class="nav-link px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 ease-in-out hover:bg-gray-700 hover:text-white flex items-center"><i class="fas fa-book-open mr-2"></i>Spec</a>
                <a href="/providers" class="nav-link px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 ease-in-out hover:bg-gray-700 hover:text-white flex items-center"><i class="fas fa-database mr-2"></i>Providers</a>
                <a href="/#section7" class="nav-link px-4 py-2 rounded-full text-sm font-medium transition-all duration-300 ease-in-out hover:bg-gray-700 hover:text-white flex items-center"><i class="fas fa-puzzle-piece mr-2"></i>Implementations</a>
            </div>
            <div class="md:hidden flex items-center">
                <button id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" aria-controls="mobile-menu" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <svg class="hidden h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>


    <div class="md:hidden hidden" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="/#section1" class="nav-link text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium flex items-center"><i class="fas fa-bolt mr-2"></i>Example</a>
            <a href="/#section2" class="nav-link text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium flex items-center"><i class="fas fa-book-open mr-2"></i>Spec</a>
            <a href="/providers" class="nav-link text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium flex items-center"><i class="fas fa-database mr-2"></i>Providers</a>
            <a href="/#section7" class="nav-link text-gray-300 hover:bg-gray-700 hover:text-white block px-3 py-2 rounded-md text-base font-medium flex items-center"><i class="fas fa-puzzle-piece mr-2"></i>Implementations</a>
        </div>
    </div>
</nav>

<?php if (basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
<header class="text-center py-20 pt-36 bg-gray-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <h1 class="text-6xl font-extrabold text-white leading-tight mb-4">oEmbed</h1>
        <p class="text-xl text-gray-400 max-w-3xl mx-auto mb-8">
            oEmbed is a format for allowing an embedded representation of a URL on third party sites. The simple API allows a website to display embedded content (such as photos or videos) when a user posts a link to that resource, without having to parse the resource directly.
        </p>
        <div class="flex flex-col items-center justify-center gap-y-6">
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a href="https://github.com/iamcal/oembed" target="_blank" rel="noopener noreferrer" class="transition-opacity hover:opacity-80">
                    <img src="https://img.shields.io/github/stars/iamcal/oembed?style=for-the-badge&logo=github" alt="GitHub stars">
                </a>
                <a href="https://www.npmjs.com/package/oembed-providers" target="_blank" rel="noopener noreferrer" class="transition-opacity hover:opacity-80">
                    <img src="https://img.shields.io/npm/v/oembed-providers?style=for-the-badge&logo=npm&label=npm" alt="NPM Version">
                </a>
                <a href="https://www.npmjs.com/package/oembed-providers" target="_blank" rel="noopener noreferrer" class="transition-opacity hover:opacity-80">
                    <img src="https://img.shields.io/npm/dt/oembed-providers?style=for-the-badge&logo=npm&color=blue" alt="NPM Downloads">
                </a>
            </div>
            <a href="https://github.com/iamcal/oembed" class="inline-flex items-center bg-blue-600 text-white font-bold py-3 px-8 rounded-full hover:bg-blue-700 transition-all duration-300 ease-in-out text-lg">
                <i class="fa-brands fa-github mr-2"></i>
                View on GitHub
            </a>
        </div>
    </div>
</header>
<?php endif; ?>