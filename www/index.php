<!--
                         __                 __     
                        /\ \               /\ \    
  ___      __    ___ ___\ \ \____     __   \_\ \   
 / __`\  /'__`\/' __` __`\ \ '__`\  /'__`\ /'_` \  
/\ \L\ \/\  __//\ \/\ \/\ \ \ \L\ \/\  __//\ \L\ \ 
\ \____/\ \____\ \_\ \_\ \_\ \_,__/\ \____\ \___,_\
 \/___/  \/____/\/_/\/_/\/_/\/___/  \/____/\/__,_ /

 ===== because 'open embed' sounds too dirty =====



 Document history
 ==============================================================

 2008-03-21 - original draft created by cal
 2008-03-28 - incorporated leah's edits, added security section
 2008-04-07 - added implicit format for endpoint URLs
            - clarified format and max(width|height) params
            - consumers/providers update from leah
 2008-04-08 - clarified XML value escaping
 2008-05-09 - added optional thumbnail parameters
 2008-05-12 - valid JSON would be nice i guess
 2008-05-29 - and really valid this time?
            - also fixed urlencoding in request examples
 2008-07-18 - added discovery section
            - more providers
 2009-09-21 - removed pownce
            - added providers
            - added a libraries section
            - updated video example (youtube has real support)
 2010-10-03 - history is now tracked on GitHub
            - http://github.com/iamcal/oembed

-->
<html lang="en">
<head>
<title>oEmbed</title>
<style>

body, input, textarea, select {
	font-family: Arial,Helvetica,sans-serif;
	padding: 20px 50px;
}

#main {
	margin: 0 auto;
	max-width: 900px;
	text-align: left;
}

pre {
	background-color: #eee;
	border: 1px solid #999;
	padding: 8px;
	margin: 0px 20px;
}

code {
	background-color: #eee;
	border: 1px solid #999;
	padding: 0px 2px;
}

</style>
</head>
<body>

<div id="main">

<h1>oEmbed</h1>

<p>oEmbed is a format for allowing an embedded representation of a URL on third party sites. The simple API allows a website to display embedded content (such as photos or videos) when a user posts a link to that resource, without having to parse the resource directly.</p>

<p>This document is stored on <a href="https://github.com/iamcal/oembed">GitHub</a>.</p>

<h2>Table Of Contents</h2>

<ol>
	<li><a href="#section1">Quick Example</a></li>
	<li><a href="#section2">Full Spec</a></li>
	<li><a href="#section3">Security considerations</a></li>
	<li><a href="#section4">Discovery</a></li>
	<li><a href="#section5">More examples</a></li>
	<li><a href="#section6">Authors</a></li>
	<li><a href="#section7">Implementations</a></li>
</ol>

<a name="section1" id="section1"><h2>1. Quick Example</h2></a>

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


<a name="section2" id="section2"><h2>2. Full Spec</h2></a>

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


<a name="section3" id="section3"><h2>3. Security considerations</h2></a>

<p>When a consumer displays any URLs, they will probably want to filter the URL scheme to be one of <code>http</code>, <code>https</code> or <code>mailto</code>, although providers are free to specify any valid URL. Without filtering, <code>Javascript:...</code> style URLs could be used for XSS attacks.</p>

<p>When a consumer displays HTML (as with video embeds), there's a vector for XSS attacks from the provider. To avoid this, it is recommended that consumers display the HTML in an <code>iframe</code>, hosted from another domain. This ensures that the HTML cannot access cookies from the consumer domain.</p>


<a name="section4" id="section4"><h2>4. Discovery</h2></a>

<p>oEmbed providers can choose to make their oEmbed support discoverable by adding elements to the head of their existing (X)HTML documents.</p>

<p>For example:</p>

<pre>
&lt;link rel="alternate" type="application/json+oembed"
  href="http://flickr.com/services/oembed?url=http%3A%2F%2Fflickr.com%2Fphotos%2Fbees%2F2362225867%2F&format=json"
  title="Bacon Lollys oEmbed Profile" /&gt;
&lt;link rel="alternate" type="text/xml+oembed"
  href="http://flickr.com/services/oembed?url=http%3A%2F%2Fflickr.com%2Fphotos%2Fbees%2F2362225867%2F&format=xml"
  title="Bacon Lollys oEmbed Profile" /&gt;
</pre>

<p>The URLs contained within the <code>href</code> attribute should be the full oEmbed endpoint plus URL and any needed format parameter. No other request parameters should be included in this URL.</p>

<p>The <code>type</code> attribute must contain either <code>application/json+oembed</code> for JSON responses, or <code>text/xml+oembed</code> for XML.</p>



<a name="section5" id="section5"><h2>5. More examples</h2></a>

<a name="section5.1" id="section5.1"><h3>5.1. Video example</h3></a>

<p>Request:</p>

<pre>http://www.youtube.com/oembed?url=http%3A//youtube.com/watch%3Fv%3DM3r2XDceM6A&amp;format=json</pre>

<p>Response:</p>

<pre>{
	"version": "1.0",
	"type": "video",
	"provider_name": "YouTube",
	"provider_url": "http://youtube.com/",
	"width": 425,
	"height": 344,
	"title": "Amazing Nintendo Facts",
	"author_name": "ZackScott",
	"author_url": "http://www.youtube.com/user/ZackScott",
	"html":
		"&lt;object width=\"425\" height=\"344\"&gt;
			&lt;param name=\"movie\" value=\"http://www.youtube.com/v/M3r2XDceM6A&amp;fs=1\"&gt;&lt;/param&gt;
			&lt;param name=\"allowFullScreen\" value=\"true\"&gt;&lt;/param&gt;
			&lt;param name=\"allowscriptaccess\" value=\"always\"&gt;&lt;/param&gt;
			&lt;embed src=\"http://www.youtube.com/v/M3r2XDceM6A&amp;fs=1\"
				type=\"application/x-shockwave-flash\" width=\"425\" height=\"344\"
				allowscriptaccess=\"always\" allowfullscreen=\"true\"&gt;&lt;/embed&gt;
		&lt;/object&gt;",
}</pre>

<a name="section5.2" id="section5.2"><h3>5.2. Link example</h3></a>

<p>Request:</p>

<pre>http://iamcal.com/oembed/?url=http%3A//www.iamcal.com/linklog/1206113631/&amp;format=xml</pre>

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


<a name="section6" id="section6"><h2>6. Authors</h2></a>

<ul>
    <li>Cal Henderson (cal [at] iamcal.com)</li>
    <li>Mike Malone (mjmalone [at] gmail.com)</li>
    <li>Leah Culver (leah.culver [at] gmail.com)</li>
    <li>Richard Crowley (r [at] rcrowley.org)</li>
</ul>


<a name="section7" id="section7"><h2>7. Implementations</h2></a>

<a name="section7.1" id="section7.1"><h3>7.1. Providers</h3></a>

<p>Providers are available programatically as a json file: <a href="http://oembed.com/providers.json">http://oembed.com/providers.json</a>.</p>
<p>To add new providers, please fork <a href="https://github.com/iamcal/oembed">this repo</a> on GitHub and add/modify <code>providers/*.yml</code>.</p>

<?php
	$data = array();

	$dh = opendir(__DIR__.'/../providers');
	while (($file = readdir($dh)) !== false){
		if (preg_match('!\.yml$!', $file)){
			$partial = yaml_parse_file(__DIR__."/../providers/$file");
			foreach ($partial as $row) $data[] = $row;
		}
	}

	usort($data, 'local_sort');

	function local_sort($a, $b){
		return strcasecmp($a['provider_name'], $b['provider_name']);
	}

	function format_html($html){
		return preg_replace('!`(.*?)`!', '<code>$1</code>', $html);
	}

	foreach ($data as $provider){
?>
	<p><?php echo HtmlSpecialChars($provider['provider_name']); ?> (<a href="<?php echo HtmlSpecialChars($provider['provider_url']); ?>"><?php echo HtmlSpecialChars($provider['provider_url']); ?></a>)</p>
	<?php foreach ($provider['endpoints'] as $endpoint){ ?>
		<ul>

		<?php if (isset($endpoint['schemes']) && is_array($endpoint['schemes'])) foreach ($endpoint['schemes'] as $scheme){ ?>
			<li> URL scheme: <code><?php echo HtmlSpecialChars($scheme); ?></code> </li>
		<?php } ?>

		<?php if (isset($endpoint['url'])){ ?>
			<li> API endpoint: <code><?php echo HtmlSpecialChars($endpoint['url']); ?></code>
			<?php if (isset($endpoint['formats']) && count($endpoint['formats'])){ ?>
				(only supports <code><?php echo HtmlSpecialChars(StrToLower(implode(', ', $endpoint['formats']))); ?></code>)
			<?php } ?>
			</li>
		<?php } ?>

		<?php if (isset($endpoint['docs_url'])){ ?>
			<li> Documentation: <a href="<?php echo HtmlSpecialChars($endpoint['docs_url']); ?>"><?php echo HtmlSpecialChars($endpoint['docs_url']); ?></a> </li>
		<?php } ?>

		<?php if (isset($endpoint['example_urls']) && is_array($endpoint['example_urls'])) foreach ($endpoint['example_urls'] as $example_url){ ?>
			<li> Example: <a href="<?php echo HtmlSpecialChars($example_url); ?>"><?php echo HtmlSpecialChars($example_url); ?></a> </li>
		<?php } ?>

		<?php if (isset($endpoint['notes']) && is_array($endpoint['notes'])) foreach ($endpoint['notes'] as $note){ ?>
			<li><?php echo format_html($note); ?></li>
		<?php } ?>

		<?php if (isset($endpoint['discovery'])){ ?>
		 	<li> Supports discovery via <code>&lt;link&gt;</code> tags </li>
		<?php } ?>
	</ul>
	<?php } ?>
<?php } ?>

<a name="section7.2" id="section7.2"><h3>7.2. Consumers</h3></a>

<p>To have a particular consumer display your OEmbed, please contact the consumer with your provider's URL scheme and API endpoint.</p>

<p>Buckybase (<a href="http://buckybase.appspot.com/">http://buckybase.appspot.com/</a>)</p>
<ul>
	<li> Contact: Manuel Simoni (msimoni [at] gmail.com)</li>
</ul>

<p>280 Slides (<a href="http://280slides.com/">http://280slides.com/</a>)</p>
<ul>
	<li> Contact: Ross Boucher (rboucher [at] gmail.com)</li>
</ul>

<p>Dumble (<a href="http://oohembed.com/dumble/">http://oohembed.com/dumble/</a>)</p>
<ul>
	<li> Contact: Deepak Sarda (deepak.sarda [at] gmail.com)</li>
</ul>

<p>Iframely (<a href="http://iframely.com/">http://iframely.com/</a>)</p>
<ul>
	<li> Contact: Ivan Paramonau (i.paramonau [at] gmail.com)</li>
</ul>

<a name="section7.3" id="section7.3"><h3>7.3. Libraries</h3></a>

<ul>
	<li>PHP: php-oembed (<a href="http://code.google.com/p/php-oembed/">http://code.google.com/p/php-oembed/</a>)</li>
	<li>PHP: Services_oEmbed (<a href="http://pear.php.net/package/Services_oEmbed">http://pear.php.net/package/Services_oEmbed</a>)</li>
	<li>PHP: Essence (<a href="https://github.com/felixgirault/essence">https://github.com/felixgirault/essence</a>)</li>
	<li>PHP: Embera (<a href="https://github.com/mpratt/Embera">https://github.com/mpratt/Embera</a>)</li>
	<li>Perl: Web-oEmbed (<a href="http://search.cpan.org/~miyagawa/Web-oEmbed/">http://search.cpan.org/~miyagawa/Web-oEmbed/</a>)</li>
	<li>Ruby: oembed_links (<a href="http://github.com/netshade/oembed_links">http://github.com/netshade/oembed_links</a>)</li>
	<li>Python: pyoembed (<a href="http://github.com/rafaelmartins/pyoembed/">http://github.com/rafaelmartins/pyoembed/</a>)</li>
	<li>Python: PyEmbed (<a href="http://pyembed.github.io">http://pyembed.github.io</a>)</li>
	<li>Python: python-oembed (<a href="https://github.com/abarmat/python-oembed">https://github.com/abarmat/python-oembed</a>)</li>
	<li>Django: micawber (<a href="https://github.com/coleifer/micawber">https://github.com/coleifer/micawber</a>)</li>
	<li>Java: java-oembed (<a href="https://github.com/michael-simons/java-oembed">https://github.com/michael-simons/java-oembed</a>)</li>
	<li>.Net: oEmbed API Wrapper (<a href="http://oembed.codeplex.com/">http://oembed.codeplex.com/</a>)</li>
	<li>JQuery: oEmbed API Wrapper (<a href="https://github.com/starfishmod/jquery-oembed-all">https://github.com/starfishmod/jquery-oembed-all</a>)</li>
	<li>Node.js: oEmbed API Gateway (<a href="https://github.com/itteco/iframely">https://github.com/itteco/iframely</a>)</li>	
	<li>Elixir: furlex (<a href="https://github.com/claytongentry/furlex">https://github.com/claytongentry/furlex</a>)</li>
	<li>Elixir: elixir-oembed (<a href="https://github.com/r8/elixir-oembed">https://github.com/r8/elixir-oembed</a>)</li>
	<li>Any: oEmbed API proxy endpoint for open-source projects (<a href="http://oembedapi.com">http://oembedapi.com</a>)</li>
</ul>


<hr />

<a name="links" id="links"><h3>Press and Links</h3></a>

<ul>
	<li><a href="http://groups.google.com/group/oembed/">The official oEmbed mailing list</a></li>
</ul>
<ul>
	<li><a href="http://www.webmonkey.com/tutorial/Get_Started_With_OEmbed">Webmonkey tutorial</a></li>
	<li><a href="http://leahculver.com/2008/05/29/announcing-oembed-an-open-standard-for-embedded-content/">Leah's blog</a></li>
	<li><a href="http://www.readwriteweb.com/archives/oembed_open_format.php">ReadWriteWeb</a></li>
	<li><a href="http://developer.yahoo.com/blogs/ydn/oembed-embedding-third-party-media-made-easy-7355.html">Yahoo! Developer Network</a></li>
	<li><a href="http://ajaxian.com/archives/oembed-makes-embedding-third-party-videos-and-images-a-breeze">ajaxian</a></li>
	<li><a href="http://blog.hulu.com/2008/5/27/sharing-is-easy">Hulu blog</a></li>
	<li><a href="http://qik.com/blog/124/qik-embraces-oembed-for-embedding-videos">Qik blog</a></li>
</ul>

<p>This document is stored on <a href="https://github.com/iamcal/oembed">GitHub</a>.
	Please check the <a href="http://groups.google.com/group/oembed/">mailing list</a>, fork and contribute.</p>

</div>

</body>
</html>
