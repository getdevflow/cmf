<h1 align="center">
    <a href="https://getdevflow.com/" target="_blank"><img src="https://downloads.joshuaparker.blog/images/Devflow-Logo.png" alt="Devflow CMS"></a>
</h1>

<p align="center">
    <a href="https://getdevflow.com/"><img src="https://img.shields.io/packagist/v/getdevflow/cmf?label=Devflow" alt="Devflow Stable Version"></a>
    <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.4-777BB4.svg?style=flat&logo=php" alt="PHP 8.4"/></a>
    <a href="https://github.com/getdevflow/cmf/blob/2.x/LICENSE.md"><img src="https://img.shields.io/packagist/l/getdevflow/cmf" alt="GPLv2-only"></a>
    <a href="https://packagist.org/packages/getdevflow/cmf"><img src="https://img.shields.io/packagist/dt/getdevflow/cmf" alt="Devflow Downloads"></a>
    <a href="https://discord.gg/52CyYu4e"><img src="https://img.shields.io/static/v1?label=Discord&message=chat&color=738adb&logo=discord" alt="Chat on Discord"></a>
</p>

<h1 align="center">Build high quality, optimized websites that scale</h1>

<p align="center">🌟 Star me on <a href="https://github.com/getdevflow/cmf">GitHub</a> to encourage continuous development!</p>

__Devflow CMS__ is a hybrid content management framework that gives PHP architects the freedom to build without limits.

![https://devflow-cmf.s3.us-east-1.amazonaws.com/image/Devflow-CMS-Content-Types.gif](https://devflow-cmf.s3.us-east-1.amazonaws.com/image/Devflow-CMS-Content-Types.gif)

__Devflow CMS__ is a powerful hybrid content management framework and CMS built for PHP architects, designed to streamline web development
using __CQRS__ (Command Query Responsibility Segregation) and __domain-driven development__ principles. Offering an advanced architecture,
Devflow CMS supports __event sourcing__ and __domain events__, allowing developers to easily manage complex workflows and data changes.

## 📍 Requirement
- PHP >= 8.4+
- BCMath PHP Extension
- Gettext PHP Extension
- Fileinfo PHP extension
- JSON PHP Extension
- OpenSSL PHP Extension
- PDO PHP Extension 
- Mbstring PHP Extension 
- Tokenizer PHP Extension
- Fileinfo PHP Extension
- GD Library
- Imagick PHP Extension
- XMLWriter PHP Extension

## 🏆 Highlighted Features
- Custom Content Types
- Editorial workflow for publishing teams
- Robust firewall (enabled by default)
- Security headers (enabled by default)
- Referrer Spam detection (enabled by default)
- CORS (enabled by default)
- Rate limiting (enabled by default)
- Visual page builder (for supported themes)
- Provides a simple hook and event system without affecting core code
- Scheduler for scheduling tasks/jobs
- Security and sanitizing helpers
- NIST Level 2 Standard Role-Based Access Control
- Caching (enabled by default)

## 🖼 Screenshots

|  ![https://downloads.joshuaparker.blog/images/login-screen.png](https://downloads.joshuaparker.blog/images/login-screen.png)  | ![https://downloads.joshuaparker.blog/images/devflow-dashboard.png](https://downloads.joshuaparker.blog/images/devflow-dashboard.png) |
|:-----------------------------------------------------------------------------------------------------------------------------:|:-------------------------------------------------------------------------------------------------------------------------------------:|
|                                                        *Login Screen*                                                         |                                                              *Dashboard*                                                              |
| ![https://downloads.joshuaparker.blog/images/content-types.png](https://downloads.joshuaparker.blog/images/content-types.png) |    ![https://downloads.joshuaparker.blog/images/create-product.png](https://downloads.joshuaparker.blog/images/create-product.png)    |
|                                                        *Content Types*                                                        |                                                           *Create Product*                                                            |
| ![https://downloads.joshuaparker.blog/images/custom-fields.png](https://downloads.joshuaparker.blog/images/custom-fields.png) |   ![https://downloads.joshuaparker.blog/images/devflow-plugins.png](https://downloads.joshuaparker.blog/images/devflow-plugins.png)   |
|                                                        *Custom Fields*                                                        |                                                          *Composer Plugins*                                                           |


## 📦 Installation

To create a new project, run the following command:

```bash
composer create-project getdevflow/cmf project-name
```

## 🕑 Releases

| Version | Minimum PHP Version | Release Date  | Bug Fixes Until | Security Fixes Until |
|---------|---------------------|---------------|-----------------|----------------------|
| 1 - LTS | 8.3                 | December 2024 | June 2027       | December 2028        |
| 2       | 8.4                 | May 2026      | January 2028    | April 2030           |
| 3 - LTS | 8.5                 | November 2026 | May 2029        | November 2030        |

## 📘 Documentation

Documentation is still a work in progress. Between the [Devflow Docs](https://docs.getdevflow.com/) and [CodefyPHP's](https://codefyphp.com/docs/) documentation, 
that should help you get started.

## 🙌 Sponsors

If you use and love Devflow and are interested in supporting its continued development, please consider sponsoring 
me via [Github](https://github.com/sponsors/nomadicjosh).

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
