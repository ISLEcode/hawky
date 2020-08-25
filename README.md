---
revision  : 2020-08-25 (Tue) 16:42:48
title     : Hawky README
---

We recently came accross [Datenstrom Yellow][yellow]. And I liked it. We have been stumbling on the _static site_ generation
topic for some time now. We have tried many, and none are satisfying... Jekyll, Hugo and their likes have satisfied many, not us!

Our (not so special) requirements are:

  - use (fully) Pandoc and CommonMark as the Markdown conversion engines
  - enable online editing by end users of possibly complex HTML5 pages
  - support GitHub pages for the versioning and backend storage of editorial content
  - provide wiki-like page handling for easy update and management by end users

One could debate on how this could be done in Jekyll and Hugo — and indeed we have working POCs for both tools. Jekyll is written
in Ruby, which is no longer on our technology roadmap; further such customisations would not be supported by GitHub's automated
Jekyll conversions. While the Go language is definitively on our technology roadmap, Hugo has grown into a complex beast and the
maintenance of our customisations for Hugo would incur a lot of overhead costs.

This is where Datenstrom's [Yellow][] comes in. They say it is for small web sites. We wouldn't say that. From our first peeks
this looks like an interesting Open Source project which provides a ready-made and tested framework which could be carved to our
needs. Further the design is modular with many [extensions][] of interest.

Why not simply fork the project? Hopefully our developments will be contributed back to Yellow. Old programmers have bad habits!
For now and for our programming convenience, we prefer pulling Yellow into our worflow rather than the over way round. There are
also some possibly diverging thoughts we want to investigate:

  - the generated static site should be mobile-first and PWA-ready
  - i18n is not a _server side thing_ and should be handled on the client side
  - client side editing should allow editing of _content portions_
  - a more _sophisticated_ administration panel à la Grav CMS
  - use our NodeJS-based toolchain for the build process
  - integrate CommonMark with custom extensions
  - [Grav-like](https://learn.getgrav.org/16/advanced/debugging) debugging and logging

Why Hawky? Historically ISLE's knowledge vault was an Apple HyperCard database named Hawky (circa 1991). The database lived
several lives before being converted to a Dokuwiki site (circa 2007). This could be its next housing :smile:

### Backend component stack

  | | | Language component, patterns, and extensions
  | -----------------: |-| ----------------------------------------------------------------------------------------------------- |
  | [PHP][php-hp]             | [¶][php-gh]         | _The popular general purpose scripting language for web development_ |
  | [ext-curl][extcurl-gh]    |                     | → Curl library support |
  | [ext-dom][extdom-gh]      | [¶][extdom-gh]      | → DOM API |
  | [PHP-FIG][phpfig-hp]      | [¶][phpfig-gh]      | _Moving PHP forward through collaboration and [standards][phpfig-psr]_ |
  | [PSR  1][psr01-hp]        |                     | → Basic coding standard |
  | [PSR  3][psr03-hp]        | [¶][psr03-gh]       | → Logger interface |
  | [PSR  4][psr04-hp]        |                     | → Autoloading standard |
  | [PSR  6][psr06-hp]        |                     | → Caching interface |
  | [PSR  7][psr07-hp]        | [¶][psr07-gh]       | → HTTP message interface |
  | [PSR 11][psr11-hp]        |                     | → Container interface |
  | [PSR 12][psr12-hp]        |                     | → Extended coding style guide |
  | [PSR 13][psr13-hp]        |                     | → Hypermedia links |
  | [PSR 14][psr14-hp]        |                     | → Event dispatcher |
  | [PSR 15][psr15-hp]        | [¶][psr15-gh]       | → HTTP handlers |
  | [PSR 16][psr16-hp]        | [¶][psr16-gh]       | → Simple cache |
  | [PSR 17][psr17-hp]        |                     | → HTTP factories |
  | [PSR 18][psr18-hp]        |                     | → HTTP client |
  | | |  
  | | | **Core framework**
  | [Symfony][symfony-hp]     | [¶][symfony-gh]     | _Set of reusable PHP components and a PHP framework for web projects_ |
  | [Console][sy-cli-hp]      | [¶][sy-cli-gh]      | → Console component |
  | [Event dispatch][sy-ed-hp]| [¶][sy-ed-gh]       | → Event dispatcher component |
  | [Filesystem][sy-fsys-hp]  | [¶][sy-fsys-gh]     | → Filesystem component |
  | [Finder][sy-fndr-hp]      | [¶][sy-fndr-gh]     | → Finder component |
  | [Img. Optim.][sy-iop-hp]  | [¶][sy-iop-gh]      | → Easily optimize images using PHP |
  | [PF PHP 7.3+][sy-pf73-hp] | [¶][sy-pf73-gh]     | → Polyfill backporting some PHP 7.3+ features to lower PHP versions |
  | [PF Iconv][sy-iconv-hp]   | [¶][sy-iconv-gh]    | → Polyfill for the Iconv extension |
  | [Process][sy-proc-hp]     | [¶][sy-proc-gh]     | → Process component |
  | [Property][sy-prop-hp]    | [¶][sy-prop-gh]     | → PropertyAccess component |
  | [Serializer][sy-szer-hp]  | [¶][sy-szer-gh]     | → Serializer Component |
  | [Var-dumper][sf-vdump-hp] | [¶][sf-vdump-gh]    | → Mechanism for exploring and dumping PHP variables |
  | [YAML][sf-yaml-hp]        | [¶][sf-yaml-gh]     | → YAML component |
  | | |  
  | | | **Essential runtime packages**
  | [CA-bundle][cabundle-gh]  |                     | Find path to system CA bundle (with fallback to the Mozilla CA bundle) |
  | [Climate][climate-hp]     | [¶][climate-gh]     | Terminal output colored text, special formatting, and more |
  | [Crontab][cron-gh]        |                     | Cron syntax handling in PHP |
  | [DebugBar][debugbar-hp]   | [¶][debugbar-gh]    | Display profiling data from any part of your application |
  | [Doctrine][doctrine-hp]   | [¶][doctrine-gh]    | Popular cache implementation |
  | [Doctrine collections][doccol-hp] | [¶][doccol-gh] | → Library that contains classes for working with arrays of data. |
  | [DOM string][domstr-hp]   | [¶][domstr-gh]      | DOMDocument iterators allowing traversal of a DOMNode |
  | [Dot notation][dotnot-gh] |                     | Access deep data structures via a dot notation |
  | [Gregwar][gregwar-gh]     |                     | Image manipulation library. |
  | [Guzzle][guzzlepsr7-gh]   |                     | PSR-7 HTTP message library. |
  | [Humbug][humbug-gh]       |                     | accessing HTTPS resources for PHP 5.3+ (archived) |
  | [Intervention][iint-hp]   | [¶][iint-gh]        | Image handling and manipulation library (supports GD an Imagick) |
  | [Miljar][miljarexif-gh]   |                     | Object-oriented EXIF parsing |
  | [Monolog][monolog-hp]     | [¶][monolog-gh]     | Log to files, sockets, inboxes, databases and various web services |
  | [MP3 info][mp3info-gh]    |                     | The fastest php library to extract mp3 tags & meta information. |
  | [MyClabs enum][enum-gh]   |                     | PHP enum support. |
  | [Negotiation][negot-hp]   | [¶][negot-gh]       | Content Negotiation tools for PHP provided as a standalone library |
  | [Pimple][pimple-hp]       | [¶][pimple-gh]      | Simple PHP dependency injection container |
  | [Nylhom PSR7][tnpsr7-hp]  | [¶][tnpsr7-gh]      | Super lightweight PSR-7 implementation |
  | [PSR7 Server][kopsr7-gh]  |                     | Use any PSR7 implementation as your main request and response |
  | [RocketTheme][rockbox-hp] | [¶][rockbox-gh]     | RocketTheme toolbox library |
  | [Slugify][slugify-gh]     |                     | Converts a string to a slug. Integrates with Symfony and Twig. |
  | [Twig][twig-hp]           | [¶][twig-gh]        | The flexible, fast, and secure template engine for PHP |
  | [Twig defer][twigd-gh]    |                     | → An extension that allows to defer block rendering |
  | [Twig extension][twige-gh] |                    | → Common additional features for Twig that do not directly belong in core |
  | [User agent][ua-hp]       | [¶][ua-gh]          | Lightning fast, minimalist PHP user agent string parser. |
  | [Watcher][reswatcher-hp]  | [¶][reswatcher-gh]  | Resource watcher using Symfony Finder |
  | [Whoops][whoops-hp]       | [¶][whoops-gh]      | Deal with errors and exceptions in a less painful way |
  | | |  
  | | | **Optional runtime packages**
  | [Parsedown][pdown-hp]     | [¶][pdown-gh]       | Self-described as _better Markdown parser in PHP_ |
  | [Parsedown-extra][pdownx-gh] |                  | → Extension that adds [Markdown Extra][md-extra] support |
  | | |  
  | | | **PHP-implemented build tools**
  | [HTML minifier][html-min-gh] |                  | HTML compressor and minifier |
  | [Minify][minify-hp]       | [¶][minify-gh]      | CSS & JavaScript minifier, in PHP |
  | [SCSSPHP][scssphp-hp]     | [¶][scssphp-gh]     | Compiler for SCSS written in PHP |

        "ext-json": "*",
        "ext-mbstring": "*",
        "ext-openssl": "*",
        "ext-zip": "*",

  [domstr-gh]:          https://github.com/antoligy/dom-string-iterators
  [domstr-hp]:          https://packagist.org/packages/antoligy/dom-string-iterators
  [cabundle-gh]:        https://github.com/composer/ca-bundle
  [climate-gh]:         https://github.com/thephpleague/climate
  [climate-hp]:         https://climate.thephpleague.com
  [cron-gh]:            https://github.com/dragonmantank/cron-expression
  [debugbar-gh]:        https://github.com/maximebf/php-debugbar
  [debugbar-hp]:        http://phpdebugbar.com
  [dotnot-gh]:          https://github.com/dflydev/dflydev-dot-access-data
  [doctrine-gh]:        https://github.com/doctrine/cache
  [doctrine-hp]:        https://www.doctrine-project.org/projects/cache.html
  [doccol-gh]:          https://github.com/doctrine/collections
  [doccol-hp]:          https://www.doctrine-project.org/projects/collections.html
  [extcurl-gh]:         https://github.com/php-mod/curl
  [extdom-gh]:          https://github.com/PhpGt/Dom
  [extdom-hp]:          https://www.php.gt/dom
  [gregwar-gh]:         https://github.com/Gregwar/Image
  [guzzlepsr7-gh]:      https://github.com/guzzle/psr7
  [html-min-gh]:        https://github.com/voku/HtmlMin
  [humbug-gh]:          https://github.com/humbug/file_get_contents
  [iint-gh]:            https://github.com/Intervention/image
  [iint-hp]:            http://image.intervention.io
  [kopsr7-gh]:          https://github.com/kodus/psr7-server/tree/1.0.1
  [md-extra]:           https://michelf.ca/projects/php-markdown/extra
  [miljarexif-gh]:      https://github.com/PHPExif/php-exif
  [minify-gh]:          https://github.com/matthiasmullie/minify/tree/1.3.63
  [minify-hp]:          https://www.minifier.org
  [monolog-gh]:         https://github.com/Seldaek/monolog
  [monolog-hp]:         https://seldaek.github.io/monolog
  [mp3info-gh]:         https://github.com/wapmorgan/Mp3Info
  [enum-gh]:            https://github.com/myclabs/php-enum
  [negot-gh]:           https://github.com/willdurand/Negotiation
  [negot-hp]:           http://williamdurand.fr/Negotiation/
  [tnpsr7-gh]:          https://github.com/Nyholm/psr7/tree/master
  [tnpsr7-hp]:          https://tnyholm.se
  [pdown-gh]:           https://github.com/erusev/parsedown
  [pdown-hp]:           https://parsedown.org
  [pdownx-gh]:          https://github.com/erusev/parsedown-extra
  [php-gh]:             https://github.com/php/php-src
  [php-hp]:             https://www.php.net
  [pimple-gh]:          https://github.com/silexphp/Pimple/tree/master
  [pimple-hp]:          https://pimple.symfony.com
  [psr03-gh]:           https://github.com/php-fig/log
  [symfony-hp]:         https://symfony.com
  [symfony-gh]:         https://github.com/symfony
  [psr07-gh]:           https://github.com/php-fig/http-message
  [psr15-gh]:           https://github.com/php-fig/http-server-middleware
  [psr16-gh]:           https://github.com/php-fig/simple-cache
  [reswatcher-gh]:      https://github.com/yosymfony/resource-watcher
  [reswatcher-hp]:      http://yosymfony.com/
  [rockbox-gh]:         https://github.com/rockettheme/toolbox
  [rockbox-hp]:         http://www.rockettheme.com/
  [scssphp-gh]:         https://github.com/scssphp/scssphp
  [scssphp-hp]:         http://scssphp.github.io/scssphp/
  [sf-vdump-gh]:        https://github.com/symfony/var-dumper
  [sf-vdump-hp]:        https://symfony.com/
  [sf-yaml-gh]:         https://github.com/symfony/yaml
  [sf-yaml-hp]:         https://symfony.com/
  [slugify-gh]:         https://github.com/cocur/slugify
  [sy-cli-gh]:          https://github.com/symfony/console
  [sy-cli-hp]:          https://symfony.com/
  [sy-ed-gh]:           https://github.com/symfony/event-dispatcher
  [sy-ed-hp]:           https://symfony.com/
  [sy-fsys-gh]:         https://github.com/symfony/filesystem
  [sy-fsys-hp]:         https://symfony.com/
  [sy-fndr-gh]:         https://github.com/symfony/finder
  [sy-fndr-hp]:         https://symfony.com/
  [sy-iop-gh]:          https://github.com/spatie/image-optimizer
  [sy-iop-hp]:          https://symfony.com/
  [sy-pf73-gh]:         https://github.com/symfony/polyfill-php73
  [sy-pf73-hp]:         https://symfony.com/
  [sy-iconv-gh]:        https://github.com/symfony/polyfill-iconv
  [sy-iconv-hp]:        https://symfony.com/
  [sy-proc-gh]:         https://github.com/symfony/process
  [sy-proc-hp]:         https://symfony.com/
  [sy-prop-gh]:         https://github.com/symfony/property-access
  [sy-prop-hp]:         https://symfony.com/
  [sy-szer-gh]:         https://github.com/symfony/serializer
  [sy-szer-hp]:         https://symfony.com/
  [twig-gh]:            https://github.com/twigphp/Twig
  [twig-hp]:            https://twig.symfony.com
  [twigd-gh]:           https://github.com/rybakit/twig-deferred-extension
  [twige-gh]:           https://github.com/twigphp/Twig-extensions
  [ua-gh]:              https://github.com/donatj/PhpUserAgent/tree/master
  [ua-hp]:              https://donatstudios.com/PHP-Parser-HTTP_USER_AGENT
  [whoops-gh]:          https://github.com/filp/whoops
  [whoops-hp]:          http://filp.github.io/whoops/
  [psr01-hp]:           https://www.php-fig.org/psr/psr-1
  [psr03-hp]:           https://www.php-fig.org/psr/psr-3
  [psr04-hp]:           https://www.php-fig.org/psr/psr-4
  [psr06-hp]:           https://www.php-fig.org/psr/psr-6
  [psr07-hp]:           https://www.php-fig.org/psr/psr-7
  [psr11-hp]:           https://www.php-fig.org/psr/psr-11
  [psr12-hp]:           https://www.php-fig.org/psr/psr-12
  [psr13-hp]:           https://www.php-fig.org/psr/psr-13
  [psr14-hp]:           https://www.php-fig.org/psr/psr-14
  [psr15-hp]:           https://www.php-fig.org/psr/psr-15
  [psr16-hp]:           https://www.php-fig.org/psr/psr-16
  [psr17-hp]:           https://www.php-fig.org/psr/psr-17
  [psr18-hp]:           https://www.php-fig.org/psr/psr-18
  [phpfig-hp]:          https://www.php-fig.org
  [phpfig-gh]:          https://github.com/php-fig/fig-standards
  [phpfig-psr]:         https://www.php-fig.org/psr
  [yellow]:             https://github.com/datenstrom/yellow
  [extensions]:         https://github.com/datenstrom/yellow-extensions
