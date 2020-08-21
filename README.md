---
revision  : 2020-08-21 (Fri) 12:23:11
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

Why Hawky? Historically ISLE's knowledge vault was an Apple HyperCard database named Hawky (circa 1991). The database lived
several lives before being converted to a Dokuwiki site (circa 2007). This could be its next housing :smile:

  [yellow]: https://github.com/datenstrom/yellow
  [extensions]: https://github.com/datenstrom/yellow-extensions
