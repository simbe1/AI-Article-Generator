=== Simbe AI Article Generator ===
Contributors: simbe1
Donate link: https://paypal.me/HBesim
Tags: AI, article generator, content, SEO, GEO
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 4.0.1
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
* **Rate Limiting**: Built-in 5 articles/day limit per user
* **Draft Publishing**: Save directly to WordPress as drafts
* **Featured Images**: Select cover images from Media Library

= How It Works =

1. Enter your article topic or paste a reference URL
2. Select an article style that fits your content needs
3. Choose length (short, medium, or long)
4. Optionally add location for GEO targeting
5. Generate and preview your article
6. Edit and save as draft to WordPress

= AI Integration =

The plugin works with or without AI. When a Groq API key is provided, it uses AI to generate more unique and contextually relevant content. Groq offers a free tier with 14,000 requests per month.

== External Services ==

This plugin connects to an external AI service to generate articles when the Groq API key is provided.

**Groq API** is used for AI-powered article generation. It processes the article topic, selected style, and user preferences to generate unique, SEO-optimized content.

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
Terms of Service: https://console.groq.com/terms
Privacy Policy: https://console.groq.com/privacy

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

By default, users can generate 5 articles per day. This can be adjusted in the code if needed.

= Is the content original? =

Yes, the generated content is unique. When using AI, each generation produces different content. When using templates, the structure varies based on the style and inputs you provide.

== Screenshots ==

1. Main article generator interface
2. Article preview with SEO settings
3. Settings page for API configuration

== Changelog ==

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

= 4.0.0 =
Major update with new UI and 15 article styles. Please backup your settings before upgrading.

== Donate ==

If you found this plugin useful, please consider making a donation to support further development.

[Donate via PayPal](https://paypal.me/HBesim)
