=== WP Publications ===
Contributors: your-username
Tags: publications, crossref, doi, academic, research
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Fetch and display academic publications from Crossref API based on DOIs.

== Description ==

WP Publications allows you to import academic publications from the Crossref API using DOIs. Each publication is created as a regular WordPress post with full metadata stored for reference.

Features:

* Import publications as regular posts using DOIs
* Automatic content generation with DOI, journal info, authors (with ORCID links), abstract, and images
* Full Crossref API response stored as metadata
* Journal information fetched and stored separately
* Select2-powered tag selection
* Progress bar during import
* Respects Crossref polite pool with configurable delays
* **Publications block**: Display publications filtered by tags, grouped by year in APA format

== Installation ==

1. Upload the `wp-publications` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Publications > Settings and configure your email address
4. Start importing publications via Publications > Import

== Configuration ==

Before importing, you must configure:

1. **Email Address** (required): Used for Crossref's "polite pool" to get better rate limits
2. **API Delay** (optional): Milliseconds between requests (default: 1000ms)

== Usage ==

1. Navigate to Publications > Import
2. Enter DOIs (one per line, or comma-separated)
3. Optionally select existing tags to associate with imports
4. Click "Import Publications"
5. Monitor progress via the progress bar

== Changelog ==

= 1.0.0 =
* Initial release
