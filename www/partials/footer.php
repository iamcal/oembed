<footer class="bg-gray-900 border-t border-gray-800">
    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="md:flex md:items-start md:justify-between">
            <div class="flex-1 min-w-0">
                 <h2 class="text-2xl font-bold text-white">oEmbed</h2>
                <p class="mt-2 text-gray-400 text-base">oEmbed is a format for allowing an embedded representation of a URL on third party sites. The simple API allows a website to display embedded content (such as photos or videos) when a user posts a link to that resource, without having to parse the resource directly.</p>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-8 md:mt-0 md:grid-cols-2 md:ml-12">
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase flex items-center"><i class="fas fa-compass mr-2"></i>Navigation</h3>
                    <ul role="list" class="mt-4 space-y-4">
                        <li><a href="/#section2" class="text-base text-gray-300 hover:text-white">Full Spec</a></li>
                        <li><a href="/#section3" class="text-base text-gray-300 hover:text-white">Security</a></li>
                        <li><a href="/#section4" class="text-base text-gray-300 hover:text-white">Discovery</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-400 tracking-wider uppercase flex items-center"><i class="fas fa-users mr-2"></i>Community</h3>
                    <ul role="list" class="mt-4 space-y-4">
                        <li><a href="/#section7" class="text-base text-gray-300 hover:text-white">Implementations</a></li>
                        <li><a href="/#section6" class="text-base text-gray-300 hover:text-white">Authors</a></li>
                        <li><a href="http://groups.google.com/group/oembed/" class="text-base text-gray-300 hover:text-white">Mailing List</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="mt-8 border-t border-gray-800 pt-8 md:flex md:items-center md:justify-between">
            <p class="text-base text-gray-400">&copy; 2008-<?php echo date("Y"); ?> oEmbed. All rights reserved.</p>
            <a href="https://github.com/iamcal/oembed" class="flex items-center text-gray-400 hover:text-white mt-4 md:mt-0">
                <span class="sr-only">GitHub</span>
                <i class="fab fa-github fa-lg"></i>
            </a>
        </div>
    </div>
</footer>

<script>
    const sections = document.querySelectorAll('section[id^="section"]');
    const navLinks = document.querySelectorAll('.nav-link');
    const navHeight = document.querySelector('nav').offsetHeight;

    window.addEventListener('scroll', () => {
        let current = '';

        sections.forEach(section => {
            const sectionTop = section.offsetTop - navHeight - 20;
            if (pageYOffset >= sectionTop) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').substring(1) === current) {
                link.classList.add('active');
            }
        });
        
        if (sections.length > 0 && window.pageYOffset < sections[0].offsetTop - navHeight - 20) {
             navLinks.forEach(link => link.classList.remove('active'));
        }
    });

    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileNavLinks = mobileMenu.querySelectorAll('.nav-link');

    mobileMenuButton.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
        const isExpanded = mobileMenuButton.getAttribute('aria-expanded') === 'true';
        mobileMenuButton.setAttribute('aria-expanded', !isExpanded);
        mobileMenuButton.querySelectorAll('svg').forEach(svg => svg.classList.toggle('hidden'));
    });

    mobileNavLinks.forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
            mobileMenuButton.setAttribute('aria-expanded', 'false');
            mobileMenuButton.querySelectorAll('svg').forEach((svg, index) => {
                svg.classList.toggle('hidden', index === 1);
            });
        });
    });

    document.addEventListener("DOMContentLoaded", function() {
        const lazyCards = document.querySelectorAll('.lazy-card');

        const lazyLoad = (target) => {
            const io = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const card = entry.target;
                        const images = card.querySelectorAll('img[data-src]');
                        
                        images.forEach(img => {
                            img.setAttribute('src', img.getAttribute('data-src'));
                            img.onload = () => {
                                img.removeAttribute('data-src');
                            };
                        });

                        card.classList.add('loaded');
                        observer.disconnect();
                    }
                });
            });

            io.observe(target);
        };

        lazyCards.forEach(lazyLoad);
    });
</script>
</body>
</html>