<?php require_once('partials/header.php'); ?>

<div id="main" class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8 pt-24">

<section id="providers" class="scroll-mt-24">
    <header class="text-center mb-12">
        <h1 class="text-5xl font-extrabold text-white leading-tight">oEmbed Providers</h1>
        <p class="mt-4 text-xl text-gray-400 max-w-3xl mx-auto">
            List of all providers that support the oEmbed.
        </p>
    </header>

    <?php
        $data = array();
        $providers_dir = __DIR__.'/../providers';
        $error_message = null;

        if (!function_exists('yaml_parse_file')) {
            $error_message = "The YAML PECL extension is not installed or enabled. Please contact the server administrator.";
        } elseif (!is_dir($providers_dir) || !is_readable($providers_dir)) {
            $error_message = "The providers directory is missing or not readable. Please check the installation.";
        } else {
            $dh = opendir($providers_dir);
            if ($dh) {
                while (($file = readdir($dh)) !== false){
                    if (preg_match('!\\.yml$!', $file)){
                        $file_path = $providers_dir."/".$file;
                        if (is_readable($file_path)) {
                            $partial = yaml_parse_file($file_path);
                            if (is_array($partial)) {
                                foreach ($partial as $row) $data[] = $row;
                            }
                        }
                    }
                }
                closedir($dh);
            }
        }
        
        $total_providers = count($data);
        $limit = 20;
        $total_pages = $total_providers > 0 ? ceil($total_providers / $limit) : 0;
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        if ($page > $total_pages) $page = $total_pages > 0 ? $total_pages : 1;
        $offset = ($page - 1) * $limit;
        $providers_on_page = array_slice($data, $offset, $limit);
    ?>

    <?php if ($error_message): ?>
        <div class="bg-red-900/50 border border-red-500 text-red-300 px-4 py-3 rounded-lg text-center">
            <h3 class="font-bold">System Error</h3>
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php else: ?>
        <p class="text-center text-lg text-gray-400 mb-6">
            Displaying <span class="font-bold text-white"><?php echo count($providers_on_page); ?></span> of <span class="font-bold text-white"><?php echo number_format($total_providers); ?></span> community-maintained providers.
        </p>
        <p class="text-center text-sm text-gray-500 italic mb-12">Badge information is generated in real-time by shields.io and may not reflect the immediate status of every endpoint.</p>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($providers_on_page as $provider): ?>
                <div class="lazy-card bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
                    <div class="flex-grow">
                        <div class="flex items-center gap-x-3 mb-2">
                            <?php $domain = parse_url($provider['provider_url'], PHP_URL_HOST); ?>
                            <img data-src="https://www.google.com/s2/favicons?domain=<?php echo htmlspecialchars($domain); ?>&sz=32" alt="Favicon" class="w-6 h-6 rounded-full">
                            <h4 class="text-xl font-bold text-white"><?php echo htmlspecialchars($provider['provider_name']); ?></h4>
                            <img data-src="https://img.shields.io/website?url=<?php echo urlencode($provider['provider_url']); ?>&label=&up_message=Online&down_message=Offline&style=flat&up_color=22c55e&down_color=ef4444" alt="Uptime">
                        </div>
                        <a href="<?php echo htmlspecialchars($provider['provider_url']); ?>" target="_blank" rel="nofollow noopener noreferrer" class="text-blue-400 break-all text-sm inline-block hover:underline">
                            <?php echo htmlspecialchars($provider['provider_url']); ?> <i class="fas fa-external-link-alt fa-xs"></i>
                        </a>
                        <?php if (isset($provider['description'])): ?>
                            <p class="text-gray-400 text-sm mt-3"><?php echo htmlspecialchars($provider['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-auto pt-4 border-t border-gray-700 mt-4">
                        <h5 class="text-sm font-semibold text-gray-400 tracking-wider uppercase mb-3">Endpoints</h5>
                        <?php foreach($provider['endpoints'] as $endpoint): ?>
                            <div class="mb-3 last:mb-0">
                                <p class="text-xs text-gray-300 break-all bg-gray-700/50 rounded p-2"><?php echo htmlspecialchars($endpoint['url']); ?></p>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    <?php if (isset($endpoint['discovery']) && $endpoint['discovery']): ?>
                                        <span class="text-xs bg-green-900/50 text-green-300 px-2 py-0.5 rounded-full">Discovery</span>
                                    <?php endif; ?>
                                    <?php if (isset($endpoint['formats'])): ?>
                                        <?php foreach($endpoint['formats'] as $format): ?>
                                            <span class="text-xs bg-blue-900/50 text-blue-300 px-2 py-0.5 rounded-full"><?php echo htmlspecialchars($format); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (isset($provider['schemes'])): ?>
                            <h5 class="text-sm font-semibold text-gray-400 tracking-wider uppercase mb-3 mt-4 border-t border-gray-700 pt-4">Schemes</h5>
                            <?php foreach($provider['schemes'] as $scheme): ?>
                                <p class="text-xs text-gray-300 break-all bg-gray-700/50 rounded p-2 mb-2 last:mb-0"><?php echo htmlspecialchars($scheme); ?></p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if (isset($provider['example_urls'])): ?>
                            <h5 class="text-sm font-semibold text-gray-400 tracking-wider uppercase mb-3 mt-4 border-t border-gray-700 pt-4">Example URLs</h5>
                            <?php foreach($provider['example_urls'] as $example_url): ?>
                                <p class="text-xs text-gray-300 break-all bg-gray-700/50 rounded p-2 mb-2 last:mb-0"><?php echo htmlspecialchars($example_url); ?></p>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if($total_pages > 1): ?>
            <nav class="flex items-center justify-between border-t border-gray-700 px-4 sm:px-0 mt-12 pt-8">
                <div class="-mt-px w-0 flex-1 flex">
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="border-t-2 border-transparent pt-4 pr-1 inline-flex items-center text-sm font-medium text-gray-400 hover:text-white hover:border-gray-300">
                            <i class="fas fa-arrow-left mr-3"></i> Previous
                        </a>
                    <?php endif; ?>
                </div>
                <div class="hidden md:-mt-px md:flex">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo ($i == $page) ? 'border-blue-500 text-white' : 'border-transparent text-gray-400 hover:text-white hover:border-gray-300'; ?> border-t-2 pt-4 px-4 inline-flex items-center text-sm font-medium">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <div class="-mt-px w-0 flex-1 flex justify-end">
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="border-t-2 border-transparent pt-4 pl-1 inline-flex items-center text-sm font-medium text-gray-400 hover:text-white hover:border-gray-300">
                            Next <i class="fas fa-arrow-right ml-3"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

</div>

<?php require_once('partials/footer.php'); ?>