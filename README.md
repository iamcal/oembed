<h1 align="center">
  <a href="https://github.com/iamcal/oembed">
    <img src="docs/images/logo.svg" alt="Logo" width="100" height="100">
  </a>
</h1>

<div align="center">
  <br />
  <a href="#about"><strong>Explore the docs »</strong></a>
  <br />
  <br />
  <a href="https://github.com/iamcal/oembed/issues/new?assignees=&labels=bug&template=01_BUG_REPORT.md&title=bug%3A+">Report a Bug</a>
  ·
  <a href="https://github.com/iamcal/oembed/issues/new?assignees=&labels=enhancement&template=02_FEATURE_REQUEST.md&title=feat%3A+">Request a Feature</a>
  .
  <a href="https://github.com/iamcal/oembed/issues/new?assignees=&labels=question&template=04_SUPPORT_QUESTION.md&title=support%3A+">Ask a Question</a>
</div>

<div align="center">
<br />

[![Project license](https://img.shields.io/github/license/iamcal/oembed.svg?style=flat-square)](LICENSE)

[![Build Status](https://github.com/iamcal/oembed/actions/workflows/build.yml/badge.svg)](https://github.com/iamcal/oembed/actions)
<span class="badge-npmversion"><a href="https://npmjs.org/package/oembed-providers" title="View this project on NPM"><img src="https://img.shields.io/npm/v/oembed-providers.svg" alt="NPM version" /></a></span>

</div>

<details>
<summary>Table of Contents</summary>

- [About](#about)
  - [What is oEmbed?](#what-is-oembed)
  - [Repository Overview](#repository-overview)
  - [Project Structure](#project-structure)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Development Setup](#development-setup)
- [Usage](#usage)
  - [Consuming the Provider Registry](#consuming-the-provider-registry)
  - [Provider Configuration](#provider-configuration)
  - [Code Examples](#code-examples)
- [Provider Management](#provider-management)
  - [Available Providers](#available-providers)
  - [Adding a New Provider](#adding-a-new-provider)
  - [Provider Configuration Format](#provider-configuration-format)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [Support](#support)
- [License](#license)

</details>

---

## About

### What is oEmbed?

oEmbed is a format for allowing an embedded representation of a URL on third party sites. The simple API allows a website to display embedded content (such as photos or videos) when a user posts a link to that resource, without having to parse the resource directly.

Key features:
- Simple HTTP-based protocol
- Support for photos, videos, links, and rich content
- Standardized response format
- Wide adoption by major content providers

### Repository Overview

This repository serves two main purposes:

1. **oEmbed Specification**: Contains the current oEmbed spec as seen at [oembed.com](http://oembed.com) and any drafts in the `www` directory.
2. **Provider Registry**: Maintains configuration information for oEmbed providers as YAML files in the `providers` directory.

## Getting Started

### Prerequisites

To work with this repository, you'll need:

- **Web Server**: Nginx or Apache
- **PHP**: For running the specification website
- **Node.js**: Version 22 or higher
- **npm**: For package management

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/iamcal/oembed.git
   cd oembed
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

### Development Setup

1. Configure your development environment:
   ```bash
   # Install development dependencies
   npm install --dev
   
   # Set up pre-commit hooks
   npm run prepare
   ```

2. Run tests:
   ```bash
   npm test
   ```

## Usage

### Consuming the Provider Registry

Install the package via npm:

```bash
npm install oembed-providers
```

The provider registry will be available at:
```
node_modules/oembed-providers/providers.json
```

### Provider Configuration

Each provider is configured using a YAML file in the `providers` directory. The configuration specifies:

- Provider name and URL
- Endpoint information
- Supported URL schemes
- Discovery settings
- Documentation links

### Code Examples

**Basic oEmbed Request:**
```javascript
// Example: Fetching oEmbed data from Flickr
const url = 'http://www.flickr.com/services/oembed/';
const params = new URLSearchParams({
  format: 'json',
  url: 'http://www.flickr.com/photos/bees/2341623661/'
});

fetch(`${url}?${params}`)
  .then(response => response.json())
  .then(data => console.log(data));
```

**Example Response:**
```json
{
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
}
```

## Provider Management

### Available Providers

The registry includes providers such as:
- YouTube
- Vimeo
- Twitter
- Instagram
- Flickr
- And many more...

For a complete list, browse the `providers` directory.

### Adding a New Provider

1. Create a new YAML file in the `providers` directory
2. Follow the provider configuration format
3. Submit a pull request

### Provider Configuration Format

```yaml
---
- provider_name: Example Provider
  provider_url: https://example.com
  endpoints:
  - schemes:
    - https://example.com/watch/*
    - https://example.com/v/*
    url: https://example.com/oembed
    docs_url: https://example.com/docs/oembed
    example_urls:
    - https://example.com/oembed?url=https://example.com/watch/123
    discovery: true
```

## Contributing

We welcome contributions! Please read our [Contributing Guidelines](docs/CONTRIBUTING.md) before submitting pull requests.

Key areas for contribution:
- Adding new providers
- Updating existing provider configurations
- Improving documentation
- Fixing bugs
- Adding tests

## Support

Need help? Here's how to get support:

- [GitHub Issues](https://github.com/iamcal/oembed/issues/new?assignees=&labels=question&template=04_SUPPORT_QUESTION.md&title=support%3A+)
- [Contact the maintainer](https://github.com/iamcal)
- Visit [iamcal.com](https://www.iamcal.com/)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.