<h1 align="center">
    <a href="https://getdevflow.com/" target="_blank"><img src="https://downloads.joshuaparker.blog/images/Devflow Logo.png" width="400" alt="Devflow Logo"></a>
</h1>

<p align="center">
    <a href="https://getdevflow.com/"><img src="https://img.shields.io/packagist/v/getdevflow/cmf?label=Devflow" alt="Latest Stable Version"></a>
    <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.3-777BB4.svg?style=flat&logo=php" alt="PHP 8.3"/></a>
    <a href="https://packagist.org/packages/getdevflow/cmf"><img src="https://img.shields.io/packagist/l/getdevflow/cmf" alt="License"></a>
    <a href="https://packagist.org/packages/getdevflow/cmf"><img src="https://img.shields.io/packagist/dt/getdevflow/cmf" alt="Total Downloads"></a>
</p>

Devflow is a headless, domain-driven content management framework created specifically for PHP programmers wanting to build bespoke websites.

## 📍 Requirement
- PHP 8.3+
- OpenSSL PHP Extension
- PDO PHP Extension 
- Mbstring PHP Extension 
- Tokenizer PHP Extension
- Fileinfo PHP Extension
- GD Library
- Imagick PHP Extension

## 🏆 Highlighted Features
- Domain-Driven
- Event sourced
- Custom Content Types
- Ability to customize admin dashboard
- Provides a simple hook and event system without affecting core code
- Scheduler for scheduling tasks/jobs
- Security and sanitizing helpers
- NIST Level 2 Standard Role-Based Access Control

## 📦 Installation

To create a new project, run the following command:

```bash
composer create-project getdevflow/cmf my-app-name
```

## 🕑 Releases

| Version | Minimum PHP Version | Release Date  | Bug Fixes Until | Security Fixes Until |
|---------|---------------------|---------------|-----------------|----------------------|
| 1 - LTS | 8.3                 | December 2024 | June 2027       | December 2028        |
| 2       | 8.4                 | December 2025 | September 2027  | March 2028           |
| 3 - LTS | 8.4                 | December 2026 | June 2029       | December 2030        |

## 📘 Documentation

Documentation is still a work in progress. Between the [Devflow Docs](https://docs.getdevflow.com/), [Qubus Components](https://docs.qubusphp.com/) documentation,
and [CodefyPHP's](https://codefyphp.com/documentation/) documentation, that should help you get started.

## 🙌 Sponsors

If you use and love Devflow and are interested in supporting its continued development, please consider sponsoring me via [Github](https://github.com/sponsors/nomadicjosh).

## 🖋 Contributing

Devflow could always be better! If you are interested in contributing enhancements or bug fixes, here are a few
rules to follow in order to ease code reviews, and discussions before I accept and merge your work.
- You MUST follow the [QubusPHP Coding Standards](https://github.com/QubusPHP/qubus-coding-standard) and PSR-12.
- You MUST write (or update) unit tests.
- You SHOULD write documentation.
- Please, write [commit messages that make sense](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html),
  and rebase your branch before submitting your Pull Request.
- Please [squash your commits](http://gitready.com/advanced/2009/02/10/squashing-commits-with-rebase.html) too.
  This is used to "clean" your Pull Request before merging it (I don't want commits such as `fix tests`, `fix 2`, `fix 3`,
  etc.).

## 🔐 Security Vulnerabilities

If you discover a vulnerability in the code, please email [joshua@joshuaparker.dev](mailto:joshua@joshuaparker.dev).

## 📄 License

Devflow is opensource software licensed under the [GPLv2](https://opensource.org/license/gpl-2-0).