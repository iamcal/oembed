## Template:

```` yaml
---
- provider_name: The Provider
  provider_url: http://www.provider.com
  endpoints:
  - schemes:
    - http://img.provider.com/*
    - http://provider.com/post/*
    url: http://api.provider.com/oembed.json
    docs_url: http://dev.provider.com/api/oembed/
    example_urls:
    - http://api.provider.com/oembed.json?url=http://provider.com/post/f00ba2
    discovery: true
    notes: Provider only supports the 'rich' type
...
````
