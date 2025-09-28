# Contributing to oEmbed

Thank you for considering contributing to the oEmbed! Your help is essential for keeping the provider registry up-to-date and the project healthy.

This document provides a set of guidelines for contributing to this repository.

## How Can I Contribute?

There are many ways to contribute to the project, and we appreciate all of them.

*   **Reporting Bugs:** If you find a bug in the website or the validation scripts, please [open a bug report](https://github.com/iamcal/oembed/issues/new?template=bug-report.yml).
*   **Suggesting Enhancements:** If you have an idea for a new feature or an improvement to the project, please [open a feature request](https://github.com/iamcal/oembed/issues/new?template=feature-request.yml).
*   **Adding New Providers:** This is the most common way to contribute! If you know of an oEmbed provider that is not yet in our registry, please [submit a provider request](https://github.com/iamcal/oembed/issues/new?template=provider-request.yml).
*   **Pull Requests:** If you're able to fix a bug or implement a feature yourself, we welcome pull requests.

### Adding a New Provider

To add a new provider to the live registry, please follow these steps:

1.  **Create a New File:** Create a new `.yml` file in the `/providers` directory. The filename should be the provider's name in lowercase (e.g., `provider.yml`).
2.  **Fill out the Details:** The YAML file must contain the provider's information. Use the following template:

    ```yaml
    - provider_name: Example Provider
      provider_url: https://www.example.com/
      endpoints:
      - schemes:
        - https://www.example.com/media/*
        - http://example.com/photos/*
        url: https://www.example.com/oembed
        discovery: true
        example_urls:
        - https://www.example.com/oembed?url=https%3A%2F%2Fwww.example.com%2Fmedia%2F123
        - https://www.example.com/oembed?url=http%3A%2F%2Fexample.com%2Fphotos%2Fabc
    ```

3.  **Provide Example URLs:** The `example_urls` are **critical**. Our automated GitHub Action uses these URLs to continuously validate that the provider's endpoint is working correctly. Please provide at least one full, working example URL.

4.  **Submit a Pull Request:** Once you have created your new `.yml` file, submit it as a pull request. The validation workflow will automatically run and test your new provider.

## Pull Request Process

1.  Create a new branch for your changes (`git checkout -b your-branch-name`).
2.  Make your changes and commit them with a clear, descriptive message.
3.  Push your branch to your fork on GitHub.
4.  Open a pull request to the `master` branch of the original repository.
5.  Provide a clear title and description for your pull request, explaining the "what" and "why" of your changes.
6.  The automated workflows will run. Make sure all checks pass.

Thank you for your contribution!