<?php require_once('partials/header.php'); ?>

<div id="main" class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8 pt-24">

<section id="section1" class="scroll-mt-24 bg-gray-800/50 rounded-2xl p-8 shadow-xl mb-12">
    <h2 class="!text-3xl !mt-0 !border-b-0 flex items-center"><i class="fas fa-bolt mr-3 text-blue-400"></i>1. Quick Example</h2>
    <div class="mt-6">

<p>A <i>consumer</i> (e.g. <a href="http://codex.wordpress.org/Embeds/">WordPress</a>) makes the following HTTP request:</p>

<ul>
	<li> <code>http://www.flickr.com/services/oembed/?format=json&url=http%3A//www.flickr.com/photos/bees/2341623661/</code> </li>
</ul>

<p>The <i>provider</i> (e.g. <a href="http://www.flickr.com/">Flickr</a>) then responds with an oEmbed response:</p>

<pre>{
	"version": "1.0",
	"type": "photo",
	"width": 240,
	"height": 160,
	"title": "ZB8T0193",
	"url": "http://farm4.static.flickr.com/3123/2341623661_7c99f48bbf_m.jpg",
	"author_name": "Bees",
	"author_url": "http://www.flickr.com/photos/bees/",
	"provider_name": "Flickr",
	"provider_url": "http://www.flickr.com/"
}</pre>

<p>This allows the consumer to turn a URL to a Flickr photo page into structured data to allow embedding of that photo in the consumer's website.</p>


</div>
</section>
<section id="section2" class="scroll-mt-24 bg-gray-800/50 rounded-2xl p-8 shadow-xl mb-12">
    <h2 class="!text-3xl !mt-0 !border-b-0 flex items-center"><i class="fas fa-book-open mr-3 text-blue-400"></i>2. Full Spec</h2>
    <div class="mt-6">

<p>This spec is broken into three parts - configuration, the consumer request and the provider response.</p>

<p>An oEmbed exchange occurs between a <i>consumer</i> and a <i>provider</i>. A <i>consumer</i> wishes to show an embedded representation of a third party resource on their own web site, such as a photo or an embedded video. A <i>provider</i> implements the oEmbed API to allow <i>consumers</i> to fetch that representation.</p>

<a name="section2.1" id="section2.1"><h3>2.1. Configuration</h3></a>

<p>Configuration for oEmbed is very simple. Providers must specify one or more URL scheme and API endpoint pairs. The URL scheme describes which URLs provided by the service may have an embedded representation. The API endpoint describes where the consumer may request representations for those URLs.</p>

<p>For instance:</p>

<ul>
	<li> URL scheme: <code>http://www.flickr.com/photos/*</code> </li>
	<li> API endpoint: <code>http://www.flickr.com/services/oembed/</code> </li>
</ul>

<p>The URL scheme may contain one or more wildcards (specified with an asterisk). Wildcards may be present in the domain portion of the URL, or in the path. Within the domain portion, wildcards may only be used for subdomains. Wildcards may not be used in the scheme (to support HTTP and HTTPS, provide two url/endpoint pairs).</p>

<p>Some examples:</p>

<ul>
	<li> <code>http://www.flickr.com/photos/*</code> OK </li>
	<li> <code>http://www.flickr.com/photos/*/foo/</code> OK </li>
	<li> <code>http://*.flickr.com/photos/*</code> OK </li>
	<li> <code>http://*.com/photos/*</code> NOT OK </li>
	<li> <code>*://www.flickr.com/photos/*</code> NOT OK </li>
</ul>

<p>The API endpoint must point to a URL with either HTTP or HTTPS scheme which implements the API described below.</p>


<a name="section2.2" id="section2.2"><h3>2.2. Consumer Request</h3></a>

<p>Requests sent to the API endpoint must be HTTP GET requests, with all arguments sent as query parameters. All arguments must be urlencoded (as per RFC 1738).</p>

<p>The following query parameters are defined as part of the spec:</p>

<dl>
	<dt><b><code>url</code></b> (required)</dt>
	<dd>The URL to retrieve embedding information for.</dd>

	<dt><b><code>maxwidth</code></b> (optional)</dt>
	<dd>The maximum width of the embedded resource. Only applies to some resource types (as specified below). For supported resource types, this parameter <i>must</i> be respected by providers.</dd>

	<dt><b><code>maxheight</code></b> (optional)</dt>
	<dd>The maximum height of the embedded resource. Only applies to some resource types (as specified below). For supported resource types, this parameter <i>must</i> be respected by providers.</dd>

	<dt><b><code>format</code></b> (optional)</dt>
	<dd>The required response format. When not specified, the provider can return any valid response format. When specified, the provider <i>must</i> return data in the request format, else return an error (see below for error codes).</dd>
</dl>

<p>Providers should ignore all other arguments it doesn't expect. Providers are welcome to support custom additional parameters.</p>

<p>Some examples:</p>

<ul>
	<li> <code>http://flickr.com/services/oembed?url=http%3A//flickr.com/photos/bees/2362225867/</code> </li>
	<li> <code>http://flickr.com/services/oembed?url=http%3A//flickr.com/photos/bees/2362225867/&amp;maxwidth=300&amp;maxheight=400&amp;format=json</code> </li>
</ul>

<p>Note: Providers may choose to have the format specified as part of the endpoint URL itself, rather than as a query string parameter.</p>

<p>For instance:</p>

<ul>
	<li> URL scheme: <code>http://www.flickr.com/photos/*</code> </li>
	<li> API XML endpoint: <code>http://www.flickr.com/services/oembed.xml</code> </li>
	<li> API JSON endpoint: <code>http://www.flickr.com/services/oembed.json</code> </li>
</ul>

<p>In this case, the format parameter is not needed and will be ignored. When a provider publishes a URL scheme and API endpoint pair, they should clearly state whether the format is implicit in the endpoint or if it needs to be passed as an argument.</p>

<a name="section2.3" id="section2.3"><h3>2.3. Provider Response</h3></a>

<p>The response returned by the provider can be in either JSON or XML. Each format specifies a way of encoding name-value pairs which comprise the response data. Each format has an associated mime-type which must be returned in the <code>Content-type</code> header along with the response.</p>

<h4>2.3.1. JSON response</h4>

<p>JSON responses must contain well formed <a href="http://json.org/">JSON</a> and must use the mime-type of <code>application/json</code>. The JSON response format may be requested by the consumer by specifying a <code>format</code> of <code>json</code>.</p>

<p>For example:</p>

<pre>{
	"foo": "bar",
	"baz": 1
}</pre>

<p>The key-value pairs to be returned are specified below. All text must be UTF-8 encoded.</p>

<h4>2.3.2. XML response</h4>

<p>XML responses must use the mime-type of <code>text/xml</code>. The XML response format may be requested by the consumer by specifying a <code>format</code> of <code>xml</code>. The response body must contain well formed XML with a root element called <code>oembed</code> and child elements for each key containing the value within the element body. For example:</p>

<pre>&lt;?xml version="1.0" encoding="utf-8" standalone="yes"?&gt;
&lt;oembed&gt;
	&lt;foo&gt;bar&lt;/foo&gt;
	&lt;baz&gt;1&lt;/baz&gt;
&lt;/oembed&gt;</pre>

<p>The key-value pairs to be returned are specified below. All text must be UTF-8 encoded. Values should be escaped PCDATA. For example:</p>

<pre>&lt;?xml version="1.0" encoding="utf-8" standalone="yes"?&gt;
&lt;oembed&gt;
	&lt;html&gt;&amp;lt;b&amp;gt;awesome!&amp;lt;/b&amp;gt;&lt;/html&gt;
&lt;/oembed&gt;</pre>


<h4>2.3.4. Response parameters</h4>

<p>Responses can specify a resource type, such as <code>photo</code> or <code>video</code>. Each type has specific parameters associated with it. The following response parameters are valid for all response types:</p>

<dl>
	<dt><b><code>type</code></b> (required)</dt>
	<dd>The resource type. Valid values, along with value-specific parameters, are described below.</dd>

	<dt><b><code>version</code></b> (required)</dt>
	<dd>The oEmbed version number. This must be <code>1.0</code>.</dd>

	<dt><b><code>title</code></b> (optional)</dt>
	<dd>A text title, describing the resource.</dd>

	<dt><b><code>author_name</code></b> (optional)</dt>
	<dd>The name of the author/owner of the resource.</dd>

	<dt><b><code>author_url</code></b> (optional)</dt>
	<dd>A URL for the author/owner of the resource.</dd>

	<dt><b><code>provider_name</code></b> (optional)</dt>
	<dd>The name of the resource provider.</dd>

	<dt><b><code>provider_url</code></b> (optional)</dt>
	<dd>The url of the resource provider.</dd>

	<dt><b><code>cache_age</code></b> (optional)</dt>
	<dd>The <i>suggested</i> cache lifetime for this resource, in seconds. Consumers may choose to use this value or not.</dd>

	<dt><b><code>thumbnail_url</code></b> (optional)</dt>
	<dd>A URL to a thumbnail image representing the resource. The thumbnail must respect any <code>maxwidth</code> and <code>maxheight</code> parameters. If this parameter is present, <code>thumbnail_width</code> and <code>thumbnail_height</code> must also be present.</dd>

	<dt><b><code>thumbnail_width</code></b> (optional)</dt>
	<dd>The width of the optional thumbnail. If this parameter is present, <code>thumbnail_url</code> and <code>thumbnail_height</code> must also be present.</dd>

	<dt><b><code>thumbnail_height</code></b> (optional)</dt>
	<dd>The height of the optional thumbnail. If this parameter is present, <code>thumbnail_url</code> and <code>thumbnail_width</code> must also be present.</dd>
</dl>

<p>Providers may optionally include any parameters not specified in this document (so long as they use the same key-value format) and consumers may choose to ignore these. Consumers must ignore parameters they do not understand.</p>

<h4>2.3.4.1. The <code>photo</code> type</h4>

<p>This type is used for representing static photos. The following parameters are defined:</p>

<dl>
	<dt><b><code>url</code></b> (required)</dt>
	<dd>The source URL of the image. Consumers should be able to insert this URL into an <code>&lt;img&gt;</code> element. Only HTTP and HTTPS URLs are valid.</dd>

	<dt><b><code>width</code></b> (required)</dt>
	<dd>The width in pixels of the image specified in the <code>url</code> parameter.</dd>

	<dt><b><code>height</code></b> (required)</dt>
	<dd>The height in pixels of the image specified in the <code>url</code> parameter.</dd>
</dl>

<p>Responses of this type must obey the <code>maxwidth</code> and <code>maxheight</code> request parameters.</p>

<h4>2.3.4.2. The <code>video</code> type</h4>

<p>This type is used for representing playable videos. The following parameters are defined:</p>

<dl>
	<dt><b><code>html</code></b> (required)</dt>
	<dd>The HTML required to embed a video player. The HTML should have no padding or margins. Consumers may wish to load the HTML in an off-domain iframe to avoid XSS vulnerabilities.</dd>

	<dt><b><code>width</code></b> (required)</dt>
	<dd>The width in pixels required to display the HTML.</dd>

	<dt><b><code>height</code></b> (required)</dt>
	<dd>The height in pixels required to display the HTML.</dd>
</dl>

<p>Responses of this type must obey the <code>maxwidth</code> and <code>maxheight</code> request parameters. If a provider wishes the consumer to just provide a thumbnail, rather than an embeddable player, they should instead return a <code>photo</code> response type.</p>

<h4>2.3.4.3. The <code>link</code> type</h4>

<p>Responses of this type allow a provider to return any generic embed data (such as <code>title</code> and <code>author_name</code>), without providing either the url or html parameters. The consumer may then link to the resource, using the URL specified in the original request.</p>

<h4>2.3.4.4. The <code>rich</code> type</h4>

<p>This type is used for rich HTML content that does not fall under one of the other categories. The following parameters are defined:</p>

<dl>
	<dt><b><code>html</code></b> (required)</dt>
	<dd>The HTML required to display the resource. The HTML should have no padding or margins. Consumers may wish to load the HTML in an off-domain iframe to avoid XSS vulnerabilities. The markup should be valid XHTML 1.0 Basic.</dd>

	<dt><b><code>width</code></b> (required)</dt>
	<dd>The width in pixels required to display the HTML.</dd>

	<dt><b><code>height</code></b> (required)</dt>
	<dd>The height in pixels required to display the HTML.</dd>
</dl>

<p>Responses of this type must obey the <code>maxwidth</code> and <code>maxheight</code> request parameters.</p>

<h4>2.3.5. Errors</h4>

<p>Providers should return any error conditions as HTTP status codes. The following status codes are defined as part of the oEmbed specification:</p>

<dl>
	<dt><b><code>404 Not Found</code></b></dt>
	<dd>The provider has no response for the requested <code>url</code> parameter. This allows providers to be broad in their URL scheme, and then determine at call time if they have a representation to return.</dd>

	<dt><b><code>501 Not Implemented</code></b></dt>
	<dd>The provider cannot return a response in the requested format. This should be sent when (for example) the request includes <code>format=xml</code> and the provider doesn't support XML responses. However, providers are encouraged to support both JSON and XML.</dd>

	<dt><b><code>401 Unauthorized</code></b></dt>
	<dd>The specified URL contains a private (non-public) resource. The consumer should provide a link directly to the resource instead of embedding any extra information, and rely on the provider to provide access control.</dd>
</dl>


</div>
</section>
<section id="section3" class="scroll-mt-24 bg-gray-800/50 rounded-2xl p-8 shadow-xl mb-12">
    <h2 class="!text-3xl !mt-0 !border-b-0 flex items-center"><i class="fas fa-shield-alt mr-3 text-blue-400"></i>3. Security considerations</h2>
    <div class="mt-6">

<p>When a consumer displays any URLs, they will probably want to filter the URL scheme to be one of <code>http</code>, <code>https</code> or <code>mailto</code>, although providers are free to specify any valid URL. Without filtering, <code>Javascript:...</code> style URLs could be used for XSS attacks.</p>

<p>When a consumer displays HTML (as with video embeds), there's a vector for XSS attacks from the provider. To avoid this, it is recommended that consumers display the HTML in an <code>iframe</code>, hosted from another domain. This ensures that the HTML cannot access cookies from the consumer domain.</p>


</div>
</section>
<section id="section4" class="scroll-mt-24 bg-gray-800/50 rounded-2xl p-8 shadow-xl mb-12">
    <h2 class="!text-3xl !mt-0 !border-b-0 flex items-center"><i class="fas fa-search mr-3 text-blue-400"></i>4. Discovery</h2>
    <div class="mt-6">

<p>oEmbed providers can choose to make their oEmbed support discoverable by adding <a href="https://developer.mozilla.org/en-US/docs/Web/HTML/Element/link"><code>&lt;link&gt;</code></a> elements to the head of their existing (X)HTML documents or by setting <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Link">Link headers</a>.</p>

<p>Element example:</p>

<pre>
&lt;link rel="alternate" type="application/json+oembed"
  href="http://flickr.com/services/oembed?url=http%3A%2F%2Fflickr.com%2Fphotos%2Fbees%2F2362225867%2F&format=json"
  title="Bacon Lollys oEmbed Profile" /&gt;
&lt;link rel="alternate" type="text/xml+oembed"
  href="http://flickr.com/services/oembed?url=http%3A%2F%2Fflickr.com%2Fphotos%2Fbees%2F2362225867%2F&format=xml"
  title="Bacon Lollys oEmbed Profile" /&gt;
</pre>

<p>Header example:</p>

<pre>
Link: &lt;http://flickr.com/services/oembed?url=http%3A%2F%2Fflickr.com%2Fphotos%2Fbees%2F2362225867%2F&format=json&gt;; rel="alternate"; type="application/json+oembed"; title="Bacon Lollys oEmbed Profile"
Link: &lt;http://flickr.com/services/oembed?url=http%3A%2F%2Fflickr.com%2Fphotos%2Fbees%2F2362225867%2F&format=xml&gt;; rel="alternate"; type="text/xml+oembed"; title="Bacon Lollys oEmbed Profile"
</pre>

<p>The URLs contained within the <code>href</code> attribute or <code>uri-reference</code> within angle brackets should be the full oEmbed endpoint plus URL and any needed format parameter. No other request parameters should be included in this URL.</p>

<p>The <code>type</code> attribute must contain either <code>application/json+oembed</code> for JSON responses, or <code>text/xml+oembed</code> for XML.</p>



</div>
</section>
<section id="section5" class="scroll-mt-24 bg-gray-800/50 rounded-2xl p-8 shadow-xl mb-12">
    <h2 class="!text-3xl !mt-0 !border-b-0 flex items-center"><i class="fas fa-code mr-3 text-blue-400"></i>5. More examples</h2>
    <div class="mt-6">

<a name="section5.1" id="section5.1"><h3>5.1. Video example</h3></a>

<p>Request:</p>

<pre>https://www.youtube.com/oembed?url=https%3A//youtube.com/watch%3Fv%3DM3r2XDceM6A&format=json</pre>

<p>Response:</p>

<pre>{
	"version": "1.0",
	"type": "video",
	"provider_name": "YouTube",
	"provider_url": "https://youtube.com/",
	"width": 425,
	"height": 344,
	"title": "Amazing Nintendo Facts",
	"author_name": "ZackScott",
	"author_url": "https://www.youtube.com/user/ZackScott",
	"html":
		"&lt;object width=\"425\" height=\"344\"&gt;
			&lt;param name=\"movie\" value=\"https://www.youtube.com/v/M3r2XDceM6A&amp;fs=1\"&gt;&lt;/param&gt;
			&lt;param name=\"allowFullScreen\" value=\"true\"&gt;&lt;/param&gt;
			&lt;param name=\"allowscriptaccess\" value=\"always\"&gt;&lt;/param&gt;
			&lt;embed src=\"https://www.youtube.com/v/M3r2XDceM6A&amp;fs=1\"
				type=\"application/x-shockwave-flash\" width=\"425\" height=\"344\"
				allowscriptaccess=\"always\" allowfullscreen=\"true\"&gt;&lt;/embed&gt;
		&lt;/object&gt;",
}</pre>

<a name="section5.2" id="section5.2"><h3>5.2. Link example</h3></a>

<p>Request:</p>

<pre>http://iamcal.com/oembed/?url=http%3A//www.iamcal.com/linklog/1206113631/&format=xml</pre>

<p>Response:</p>

<pre>&lt;?xml version="1.0" encoding="utf-8" standalone="yes"?&gt;
&lt;oembed&gt;
	&lt;version&gt;1.0&lt;/version&gt;
	&lt;type&gt;link&lt;/type&gt;
	&lt;author_name&gt;Cal Henderson&lt;/author_name&gt;
	&lt;author_url&gt;http://iamcal.com/&lt;/author_url&gt;
	&lt;cache_age&gt;86400&lt;/cache_age&gt;
	&lt;provider_name&gt;iamcal.com&lt;/provider_name&gt;
	&lt;provider_url&gt;http://iamcal.com/&lt;/provider_url&gt;
&lt;/oembed&gt;</pre>


</div>
</section>
<section id="section6" class="scroll-mt-24 bg-gray-800/50 rounded-2xl p-8 shadow-xl mb-12">
    <h2 class="!text-3xl !mt-0 !border-b-0 flex items-center"><i class="fas fa-users mr-3 text-blue-400"></i>6. Authors</h2>
    <div class="mt-6">

<ul>
    <li>Cal Henderson (cal [at] iamcal.com)</li>
    <li>Mike Malone (mjmalone [at] gmail.com)</li>
    <li>Leah Culver (leah.culver [at] gmail.com)</li>
    <li>Richard Crowley (r [at] rcrowley.org)</li>
</ul>


</div>
</section>
<section id="section7" class="scroll-mt-24 bg-gray-800/50 rounded-2xl p-8 shadow-xl mb-12">
    <h2 class="!text-3xl !mt-0 !border-b-0 flex items-center"><i class="fas fa-puzzle-piece mr-3 text-blue-400"></i>7. Implementations</h2>
    <div class="mt-6">

<a name="section7.1" id="section7.1"><h3>7.1. Providers</h3></a>

<p>Providers are available programatically as a json file: <a href="https://oembed.com/providers.json">https://oembed.com/providers.json</a>.</p>

<p>To add new providers, please fork <a href="https://github.com/iamcal/oembed">this repo</a> on GitHub and add/modify <code>providers/*.yml</code>.</p>

<?php
	$data = array();

	$dh = opendir(__DIR__.'/../providers');
	while (($file = readdir($dh)) !== false){
		if (preg_match('!\\.yml$!', $file)){
			$partial = yaml_parse_file(__DIR__."/../providers/$file");
			foreach ($partial as $row) $data[] = $row;
		}
	}

	$count = count($data);
?>

<p>There are currently <i><?php echo number_format($count); ?> providers</i> in the registry.
	Providers and consumers are <b>strongly encouraged</b> to use the <a href="#section4">discovery mechanism</a>, rather than the registry.</p>


<a name="section7.2" id="section7.2"><h3>7.2. Consumers</h3></a>

<p>Many services consume oEmbed information to display link information, including WordPress and Slack.</p>

<p>There are also some tools specifically built around managing URL embeds:</p>

<ul>
	<li>Iframely (<a href="http://iframely.com/">http://iframely.com/</a>)</li>
	<li>OEmbed Link Viewer (<a href="https://oembed.link/">https://oembed.link/</a>)</li>
</ul>

<a name="section7.3" id="section7.3"><h3>7.3. Libraries</h3></a>
<p class="text-gray-400 italic mt-4">Disclaimer: These libraries are created and maintained by the community and are not official oEmbed projects.</p>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">PHP: php-oembed</h4>
            <a href="http://code.google.com/p/php-oembed/" class="text-blue-400 break-all mt-2 inline-block">http://code.google.com/p/php-oembed/</a>
        </div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">PHP: Services_oEmbed</h4>
            <a href="http://pear.php.net/package/Services_oEmbed" class="text-blue-400 break-all mt-2 inline-block">http://pear.php.net/package/Services_oEmbed</a>
        </div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">PHP: Essence</h4>
            <a href="https://github.com/felixgirault/essence" class="text-blue-400 break-all mt-2 inline-block">https://github.com/felixgirault/essence</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/felixgirault/essence?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/felixgirault/essence" alt="Last commit"><img src="https://img.shields.io/badge/author-felixgirault-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">PHP: Embera</h4>
            <a href="https://github.com/mpratt/Embera" class="text-blue-400 break-all mt-2 inline-block">https://github.com/mpratt/Embera</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/mpratt/Embera?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/mpratt/Embera" alt="Last commit"><img src="https://img.shields.io/badge/author-mpratt-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">Perl: Web-oEmbed</h4>
            <a href="http://search.cpan.org/~miyagawa/Web-oEmbed/" class="text-blue-400 break-all mt-2 inline-block">http://search.cpan.org/~miyagawa/Web-oEmbed/</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/cpan/v/Web-oEmbed" alt="CPAN Version"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">Ruby: oembed_links</h4>
            <a href="https://github.com/netshade/oembed_links" class="text-blue-400 break-all mt-2 inline-block">https://github.com/netshade/oembed_links</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/netshade/oembed_links?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/netshade/oembed_links" alt="Last commit"><img src="https://img.shields.io/badge/author-netshade-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">Python: pyoembed</h4>
            <a href="https://github.com/rafaelmartins/pyoembed/" class="text-blue-400 break-all mt-2 inline-block">https://github.com/rafaelmartins/pyoembed/</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/rafaelmartins/pyoembed?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/rafaelmartins/pyoembed" alt="Last commit"><img src="https://img.shields.io/badge/author-rafaelmartins-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">Python: PyEmbed</h4>
            <a href="https://github.com/pyembed/pyembed" class="text-blue-400 break-all mt-2 inline-block">https://github.com/pyembed/pyembed</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/pyembed/pyembed?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/pyembed/pyembed" alt="Last commit"><img src="https://img.shields.io/badge/author-pyembed-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">Python: python-oembed</h4>
            <a href="https://github.com/abarmat/python-oembed" class="text-blue-400 break-all mt-2 inline-block">https://github.com/abarmat/python-oembed</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/abarmat/python-oembed?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/abarmat/python-oembed" alt="Last commit"><img src="https://img.shields.io/badge/author-abarmat-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">Django: micawber</h4>
            <a href="https://github.com/coleifer/micawber" class="text-blue-400 break-all mt-2 inline-block">https://github.com/coleifer/micawber</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/coleifer/micawber?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/coleifer/micawber" alt="Last commit"><img src="https://img.shields.io/badge/author-coleifer-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">Java: java-oembed</h4>
            <a href="https://github.com/michael-simons/java-oembed" class="text-blue-400 break-all mt-2 inline-block">https://github.com/michael-simons/java-oembed</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/michael-simons/java-oembed?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/michael-simons/java-oembed" alt="Last commit"><img src="https://img.shields.io/badge/author-michael--simons-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">JQuery: oEmbed API Wrapper</h4>
            <a href="https://github.com/starfishmod/jquery-oembed-all" class="text-blue-400 break-all mt-2 inline-block">https://github.com/starfishmod/jquery-oembed-all</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/starfishmod/jquery-oembed-all?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/starfishmod/jquery-oembed-all" alt="Last commit"><img src="https://img.shields.io/badge/author-starfishmod-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">Node.js: oEmbed API Gateway</h4>
            <a href="https://github.com/itteco/iframely" class="text-blue-400 break-all mt-2 inline-block">https://github.com/itteco/iframely</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/itteco/iframely?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/itteco/iframely" alt="Last commit"><img src="https://img.shields.io/badge/author-itteco-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">Elixir: furlex</h4>
            <a href="https://github.com/claytongentry/furlex" class="text-blue-400 break-all mt-2 inline-block">https://github.com/claytongentry/furlex</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/claytongentry/furlex?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/claytongentry/furlex" alt="Last commit"><img src="https://img.shields.io/badge/author-claytongentry-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">Elixir: elixir-oembed</h4>
            <a href="https://github.com/r8/elixir-oembed" class="text-blue-400 break-all mt-2 inline-block">https://github.com/r8/elixir-oembed</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/r8/elixir-oembed?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/r8/elixir-oembed" alt="Last commit"><img src="https://img.shields.io/badge/author-r8-blue" alt="Author"></div>
    </div>
    <div class="bg-gray-800/60 backdrop-blur-sm border border-white/10 rounded-xl p-6 flex flex-col transition-all duration-300 hover:border-blue-400/50 hover:shadow-lg hover:shadow-blue-500/10 hover:-translate-y-1">
        <div class="flex-grow">
            <h4 class="text-lg font-bold text-white">AWS: Serverless oEmbed provider</h4>
            <a href="https://github.com/aws-samples/sample-serverless-oembed" class="text-blue-400 break-all mt-2 inline-block">https://github.com/aws-samples/sample-serverless-oembed</a>
        </div>
        <div class="mt-auto pt-4 flex items-center space-x-2 flex-wrap"><img src="https://img.shields.io/github/stars/aws-samples/sample-serverless-oembed?style=social" alt="GitHub stars"><img src="https://img.shields.io/github/last-commit/aws-samples/sample-serverless-oembed" alt="Last commit"><img src="https://img.shields.io/badge/author-aws--samples-blue" alt="Author"></div>
    </div>
</div>


</div>
</section>
<hr />

<a name="links" id="links"><h3 class="flex items-center"><i class="fas fa-link mr-3 text-blue-400"></i>Press and Links</h3></a>

<ul>
	<li><a href="http://groups.google.com/group/oembed/">The official oEmbed mailing list</a></li>
</ul>
<ul>
	<li><a href="https://web.archive.org/web/20150318024249/https://www.wired.com/2010/02/get_started_with_oembed/">Webmonkey tutorial</a></li>
	<li><a href="https://blog.leahculver.com/2008/05/announcing-oembed-an-open-standard-for-embedded-content.html">Leah's blog</a></li>
	<li><a href="http://ajaxian.com/archives/oembed-makes-embedding-third-party-videos-and-images-a-breeze">ajaxian</a></li>
</ul>

<p>This document is stored on <a href="https://github.com/iamcal/oembed">GitHub</a>.
	Please check the <a href="http://groups.google.com/group/oembed/">mailing list</a>, fork and contribute.</p>

</div>

<?php require_once('partials/footer.php'); ?>