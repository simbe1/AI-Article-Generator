=== Simbe AI Article Generator ===
Contributors: simbe1
Donate link: https://paypal.me/HBesim
Tags: AI, article generator, content, SEO, GEO
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 4.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generate SEO and GEO optimized articles with 15 writing styles. Optional AI integration with Groq for enhanced content generation.

== Description ==

Simbe AI Article Generator helps you create high-quality, SEO-optimized articles for your WordPress site. Choose from 15 different writing styles and generate content that resonates with your audience.

= Features =

* **15 Article Styles**: Guide, Opinion, News, Tutorial, Listicle, Review, Story, Explainer, Case Study, Comparison, Trend, Problem-Solution, Beginner, Advanced, and Quick Tips
* **SEO Optimized**: Generates title, meta description, and structured content
* **GEO Targeting**: Add location context to your articles
* **AI Integration**: Optional Groq API for enhanced AI-generated content (free tier available)
* **Model Selection**: Choose your Groq AI model in Settings
* **Front-End SEO**: Saved meta description, keywords, and Open Graph tags are output automatically
* **Generation Tracking**: See how many articles you've generated (with and without AI)
* **Draft Publishing**: Save directly to WordPress as drafts
* **Featured Images**: Select cover images from Media Library

= External Services =

This plugin optionally connects to the Groq API to generate AI-powered articles when the user configures a Groq API key in the plugin settings.

What data is sent:
- Article topic/title
- Selected article style (e.g., Guide, Tutorial, Review)
- Preferred length (short, medium, long)
- Location for GEO targeting (if provided)
- User's Groq API key (for authentication)

When is data sent:
- Each time the user clicks "Generate Article" with AI enabled
- Only when the Groq API key is configured in plugin settings

Service provided by: Groq, Inc.
Terms of Service: https://groq.com/terms-of-use
Privacy Policy: https://groq.com/privacy-policy

= How It Works =

1. Enter your article topic or paste a reference URL
2. Select an article style that fits your content needs
3. Choose length (short, medium, or long)
4. Optionally add location for GEO targeting
5. Generate and preview your article
6. Edit and save as draft to WordPress

= AI Integration =

The plugin works with or without AI. When a Groq API key is provided, it uses AI to generate more unique and contextually relevant content. Groq offers a free tier with 14,000 requests per month.

== Installation ==

1. Upload the `simbe-ai-article-generator` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress Plugins menu
3. Navigate to **AI Articles** in your WordPress admin menu
4. (Optional) Go to Settings and add your Groq API key

== Frequently Asked Questions ==

= Do I need an API key? =

No, the plugin works with built-in templates. However, for AI-generated content, you'll need a free Groq API key from [console.groq.com](https://console.groq.com).

= What are the article styles? =

The plugin offers 15 styles:
* **Guide** - How-to and tutorials
* **Opinion** - Debatable topics with persuasive tone
* **News** - Current events reporting
* **Tutorial** - Step-by-step instructions
* **Listicle** - Top X format articles
* **Review** - Product comparisons and reviews
* **Story** - Personal experiences
* **Explainer** - Complex topics simplified
* **Case Study** - Real-world examples
* **Comparison** - Side-by-side evaluations
* **Trend** - Industry analysis
* **Problem-Solution** - Pain point focused
* **Beginner** - Entry-level content
* **Advanced** - Expert-level content
* **Quick Tips** - Bite-sized advice

= What's the daily limit? =

By default, users can generate unlimited post per day. 

= Is the content original? =

Yes, the generated content is unique. When using AI, each generation produces different content. When using templates, the structure varies based on the style and inputs you provide.

== Screenshots ==

1. Main article generator interface
2. Article preview with SEO settings
3. Settings page for API configuration

== Changelog ==

= 4.2.0 =
* NEW: Fully redesigned, modern Settings page with gradient header and connection status
* NEW: "Test Connection" button to verify your Groq API key from Settings
* NEW: Front-end SEO output - meta description, keywords, and Open Graph tags on posts
* NEW: Choose your Groq AI model in Settings
* FIX: User edits to the generated article are now saved (editable content box)
* FIX: Meta keywords and source URL are now saved with the article
* FIX: Meta description is also stored as the post excerpt
* IMPROVE: Retry with backoff when Groq rate-limits or returns server errors
* IMPROVE: Generation tracking now records every article (with AI usage flag)
* IMPROVE: URL fetching blocks private/internal hosts (SSRF protection)
* TUNE: Dynamic year in Trend-style meta descriptions
* TUNE: Admin UI strings are translation-ready
* REMOVE: Dead code (leftover DigitalEdu script, empty stylesheet)

= 4.1.0 =
* Replaced Font Awesome with built-in Dashicons (faster, no external requests)
* Moved inline styles to CSS file for cleaner code
* Added Settings link on Plugins page
* Surface Groq API error messages in UI
* Removed Font Awesome CDN dependency
* Code cleanup and optimization

= 4.0.0 =
* Complete UI redesign
* Added 15 article styles
* GEO location targeting
* Reference URL feature for content rewriting
* Media library integration for cover images
* Rate limiting system

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 4.2.0 =
Adds front-end SEO output, model selection, save fixes, and generation tracking. No breaking changes.

= 4.1.0 =
Improved code quality - removed external Font Awesome dependency, moved to Dashicons. No breaking changes.

= 4.0.0 =
Major update with new UI and 15 article styles. Please backup your settings before upgrading.

== Donate ==

If you found this plugin useful, please consider making a donation to support further development.

[Donate via PayPal](https://paypal.me/HBesim)
