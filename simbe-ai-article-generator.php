<?php
/**
 * Plugin Name: Simbe AI Article Generator
 * Plugin URI: https://investoryspot.com/plugins
 * Description: Generate SEO & GEO optimized articles with 15 styles + optional Groq AI.
 * Version: 4.2.0
 * Requires at least: 5.0
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Author: simbe1
 * Author URI: https://investoryspot.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simbe-ai-article-generator
 */

if (!defined('ABSPATH')) exit;

function simbe1_article_generator_activate() {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}simbe1_article_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        post_id INT,
        source_url VARCHAR(500),
        topic VARCHAR(255),
        style VARCHAR(50),
        used_ai TINYINT(1) DEFAULT 0,
        generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_date (user_id, generated_at)
    ) $charset;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'simbe1_article_generator_activate');

class SimbeAI_Article_Generator {
    
    private $version = '4.2.0';
    private $styles = array();
    private $last_ai_error = '';
    
    public function __construct() {
        $this->init_styles();
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_simbe1_generate_article', array($this, 'ajax_generate_article'));
        add_action('wp_ajax_simbe1_save_article', array($this, 'ajax_save_article'));
        add_action('wp_ajax_simbe1_fetch_url', array($this, 'ajax_fetch_url'));
        add_action('wp_ajax_simbe1_test_api', array($this, 'ajax_test_api'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        add_action('wp_head', array($this, 'output_seo_meta'), 5);
    }
    
public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, array('toplevel_page_simbe1-articles', 'simbe1-articles_page_simbe1-articles-settings'))) {
            return;
        }

        wp_enqueue_style('wp-editor');
        wp_enqueue_media();
        wp_enqueue_style('dashicons');
        wp_enqueue_style(
            'simbe1-admin',
            plugin_dir_url(__FILE__) . 'inc/admin.css',
            array('dashicons'),
            $this->version
        );

        wp_enqueue_script(
            'simbe1-article-generator',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            array('jquery'),
            $this->version,
            true
        );

        wp_localize_script('simbe1-article-generator', 'simbe1Ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simbe1ajax_nonce'),
            'strings' => array(
                'fetch' => __('Fetch', 'simbe-ai-article-generator'),
                'enterUrl' => __('Please enter a URL', 'simbe-ai-article-generator'),
                'enterTopic' => __('Please enter a topic', 'simbe-ai-article-generator'),
                'fetching' => __('Fetching...', 'simbe-ai-article-generator'),
                'generating' => __('Generating...', 'simbe-ai-article-generator'),
                'generateArticle' => __('Generate Article', 'simbe-ai-article-generator'),
                'savedDraft' => __('Article saved as draft!', 'simbe-ai-article-generator'),
                'testing' => __('Testing connection...', 'simbe-ai-article-generator'),
            )
        ));
    }
    
    // Initialize 15 article styles
    private function init_styles() {
        $this->styles = array(
            'guide' => array(
                'name' => 'Guide',
                'desc' => 'How-to, tutorials',
                'title_pattern' => 'Complete Guide to {subject}',
                'tone' => 'professional'
            ),
            'opinion' => array(
                'name' => 'Opinion',
                'desc' => 'Debatable topics',
                'title_pattern' => 'Why {subject} Matters More Than You Think',
                'tone' => 'persuasive'
            ),
            'news' => array(
                'name' => 'News',
                'desc' => 'Current events',
                'title_pattern' => '{subject} in {year}: What You Need to Know',
                'tone' => 'informative'
            ),
            'tutorial' => array(
                'name' => 'Tutorial',
                'desc' => 'Step-by-step',
                'title_pattern' => 'How to {subject} in 5 Steps',
                'tone' => 'instructional'
            ),
            'listicle' => array(
                'name' => 'Listicle',
                'desc' => 'Top X topics',
                'title_pattern' => '10 Best {subject} for Beginners',
                'tone' => 'scannable'
            ),
            'review' => array(
                'name' => 'Review',
                'desc' => 'Product comparisons',
                'title_pattern' => '{subject}: Honest Review',
                'tone' => 'analytical'
            ),
            'story' => array(
                'name' => 'Story',
                'desc' => 'Personal experience',
                'title_pattern' => 'My Journey with {subject}',
                'tone' => 'personal'
            ),
            'explainer' => array(
                'name' => 'Explainer',
                'desc' => 'Complex topics',
                'title_pattern' => '{subject} Explained Simply',
                'tone' => 'educational'
            ),
            'case_study' => array(
                'name' => 'Case Study',
                'desc' => 'Real examples',
                'title_pattern' => 'How {subject} Changed Everything',
                'tone' => 'detailed'
            ),
            'comparison' => array(
                'name' => 'Comparison',
                'desc' => 'Options evaluation',
                'title_pattern' => '{subject}: Pros and Cons',
                'tone' => 'objective'
            ),
            'trend' => array(
                'name' => 'Trend',
                'desc' => 'Industry analysis',
                'title_pattern' => '{subject} Trends Shaping the Future',
                'tone' => 'forward-looking'
            ),
            'problem_solution' => array(
                'name' => 'Problem-Solution',
                'desc' => 'Pain points',
                'title_pattern' => 'Struggling with {subject}? Here\'s the Fix',
                'tone' => 'helpful'
            ),
            'beginner' => array(
                'name' => 'Beginner',
                'desc' => 'Newcomers',
                'title_pattern' => '{subject} for Beginners: Start Here',
                'tone' => 'friendly'
            ),
            'advanced' => array(
                'name' => 'Advanced',
                'desc' => 'Experts',
                'title_pattern' => 'Advanced {subject} Strategies',
                'tone' => 'technical'
            ),
            'quick_tips' => array(
                'name' => 'Quick Tips',
                'desc' => 'Bite-sized advice',
                'title_pattern' => '5 Quick Tips for {subject}',
                'tone' => 'casual'
            )
        );
    }
    
    public function add_admin_menu() {
        add_menu_page('Article Generator', 'AI Articles', 'edit_posts', 'simbe1-articles', array($this, 'render_admin_page'), 'dashicons-edit-page', 80);
        add_submenu_page('simbe1-articles', 'Settings', 'Settings', 'manage_options', 'simbe1-articles-settings', array($this, 'render_settings'));
    }
    
    public function register_settings() {
        register_setting('simbe1_articles_settings', 'simbe1_articles_options', array(
            'type' => 'array',
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
    }
    
    public function sanitize_options($options) {
        $clean = array();
        if (isset($options['groq_api_key'])) {
            $clean['groq_api_key'] = sanitize_text_field($options['groq_api_key']);
        }
        $models = $this->get_models();
        if (isset($options['model']) && isset($models[$options['model']])) {
            $clean['model'] = $options['model'];
        } else {
            $clean['model'] = 'llama-3.1-8b-instant';
        }
        return $clean;
    }
    
    // Get Groq API key
    private function get_api_key() {
        $options = get_option('simbe1_articles_options', array());
        return sanitize_text_field($options['groq_api_key'] ?? '');
    }

    // Available Groq models (production models on GroqCloud)
    private function get_models() {
        return array(
            'llama-3.3-70b-versatile' => 'Llama 3.3 70B (Recommended)',
            'llama-3.1-8b-instant'    => 'Llama 3.1 8B (Fast)',
            'openai/gpt-oss-120b'     => 'OpenAI GPT-OSS 120B',
            'openai/gpt-oss-20b'      => 'OpenAI GPT-OSS 20B (Fastest)',
        );
    }

    // Get the selected model with a safe fallback
    private function get_model() {
        $options = get_option('simbe1_articles_options', array());
        $model = sanitize_text_field($options['model'] ?? 'llama-3.1-8b-instant');
        $models = $this->get_models();
        return isset($models[$model]) ? $model : 'llama-3.1-8b-instant';
    }
    
    // Check if Groq is configured
    private function is_ai_enabled() {
        return !empty($this->get_api_key());
    }
    
    // Surface Groq API errors
    private function generate_with_ai($topic, $style, $length, $location) {
        $api_key = $this->get_api_key();
        $this->last_ai_error = '';
        
        $style_config = $this->styles[$style] ?? $this->styles['guide'];
        $tone = $style_config['tone'];
        
        $word_counts = array('short' => 800, 'medium' => 1500, 'long' => 2500);
        $target_words = $word_counts[$length] ?? 1500;
        
        $prompt = "Write a {$style} style article about: {$topic}\n\n";
        $prompt .= "Tone: {$tone}\n";
        $prompt .= "Target length: ~{$target_words} words\n";
        $prompt .= "Include: SEO title, meta description, H2/H3 headings, conclusion\n";
        
        if (!empty($location)) {
            $prompt .= "Location focus: {$location}\n";
        }
        
        $prompt .= "\nOutput format:\nTITLE: [title]\nMETA: [meta description]\nCONTENT: [full article in HTML]";
        
        $api_url = 'https://api.groq.com/openai/v1/chat/completions';
        $max_attempts = 3;
        $attempt = 0;
        $backoff = 2;

        do {
            $attempt++;
            $response = wp_remote_post($api_url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'model' => $this->get_model(),
                    'messages' => array(
                        array('role' => 'system', 'content' => 'You are an expert content writer. Write engaging, SEO-optimized articles.'),
                        array('role' => 'user', 'content' => $prompt)
                    ),
                    'temperature' => 0.7,
                    'max_tokens' => 4000
                )),
                'timeout' => 60
            ));

            if (is_wp_error($response)) {
                if ($attempt < $max_attempts) {
                    sleep($backoff);
                    $backoff *= 2;
                    continue;
                }
                $this->last_ai_error = $response->get_error_message();
                return false;
            }

            $status = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);

            // Retry on rate limits and server errors with exponential backoff.
            if ($status === 429 || $status >= 500) {
                if ($attempt < $max_attempts) {
                    $retry_after = (int) wp_remote_retrieve_header($response, 'retry-after');
                    $wait = ($retry_after > 0 && $retry_after <= 30) ? $retry_after : $backoff;
                    sleep($wait);
                    $backoff *= 2;
                    continue;
                }
                $this->last_ai_error = $body['error']['message'] ?? 'HTTP ' . $status;
                return false;
            }

            if ($status !== 200) {
                $this->last_ai_error = $body['error']['message'] ?? 'HTTP ' . $status;
                return false;
            }

            if (!empty($body['error']['message'])) {
                $this->last_ai_error = $body['error']['message'];
                return false;
            }

            break;
        } while (true);

        $content = $body['choices'][0]['message']['content'] ?? '';
        
        if (empty($content)) {
            $this->last_ai_error = 'Empty response from AI service';
            return false;
        }
        
        return $this->parse_ai_response($content, $topic, $style, $location);
    }
    
    // Parse AI response
    private function parse_ai_response($content, $topic, $style, $location) {
        $title = '';
        $meta = '';
        $article_content = '';
        
        if (preg_match('/TITLE:\s*(.+?)(?=\nMETA:|$)/is', $content, $matches)) {
            $title = trim($matches[1]);
        }
        
        if (preg_match('/META:\s*(.+?)(?=\nCONTENT:|$)/is', $content, $matches)) {
            $meta = trim($matches[1]);
        }
        
        if (preg_match('/CONTENT:\s*(.+)/is', $content, $matches)) {
            $article_content = trim($matches[1]);
        }
        
        if (empty($title)) {
            $title = $this->generate_title($topic, $style, $location);
        }
        
        if (empty($meta)) {
            $meta = $this->generate_meta_description($topic, $style);
        }
        
        if (empty($article_content)) {
            $article_content = $this->generate_with_templates($topic, $style, 'medium', $location);
        }
        
        return array(
            'title' => $title,
            'meta_description' => $meta,
            'content' => $article_content,
            'focus_keyword' => $this->extract_keyword($topic),
            'used_ai' => true
        );
    }
    
    // Generate with templates
    private function generate_with_templates($topic, $style, $length, $location) {
        $subject = $this->extract_subject($topic);
        $keyword = $this->extract_keyword($topic);
        
        $word_counts = array('short' => 800, 'medium' => 1500, 'long' => 2500);
        $target_words = $word_counts[$length] ?? 1500;
        
        $num_sections = max(4, ceil($target_words / 250));
        
        // Get style-specific templates
        $sections = $this->get_style_sections($style, $subject, $keyword, $num_sections, $location);
        $intro = $this->get_style_intro($style, $subject);
        $conclusion = $this->get_style_conclusion($style, $subject);
        
        // Build article
        $content = "<p>{$intro}</p>\n\n";
        
        foreach ($sections as $section) {
            $content .= "<h2>{$section['title']}</h2>\n<p>{$section['content']}</p>\n\n";
        }
        
        $content .= "<h2>Conclusion</h2>\n<p>{$conclusion}</p>\n";
        
        return $content;
    }
    
    // Get style-specific intro
    private function get_style_intro($style, $subject) {
        $intros = array(
            'guide' => array(
                "Understanding {$subject} is essential for anyone looking to improve their skills. This comprehensive guide breaks down the key concepts and provides actionable steps to help you succeed.",
                "Whether you're new to {$subject} or looking to level up, having the right knowledge makes all the difference. Let's explore what works and how to apply it.",
                "In this guide, we'll cover everything you need to know about {$subject}. From basics to advanced strategies, you'll find practical insights to implement right away."
            ),
            'opinion' => array(
                "There's a debate happening around {$subject}, and it's worth paying attention to. Here's my take on why this matters more than most people realize.",
                "Let me be direct: {$subject} is more important than you think. After years of working in this space, I've seen firsthand how it impacts everything.",
                "Some people dismiss {$subject}, but they're missing the bigger picture. Here's why I believe this is one of the most important topics today."
            ),
            'news' => array(
                "The landscape of {$subject} is evolving rapidly. Here's what's happening right now and what it means for you.",
                "Breaking developments in {$subject} are reshaping how we think about this space. Let's look at the latest updates.",
                "If you haven't been following {$subject} recently, you're missing some significant changes. Here's what you need to know."
            ),
            'tutorial' => array(
                "Ready to master {$subject}? This step-by-step tutorial will walk you through everything from start to finish.",
                "Learning {$subject} doesn't have to be complicated. Follow these clear steps and you'll be up and running in no time.",
                "Let's break down {$subject} into simple, manageable steps. By the end, you'll have a solid understanding and practical skills."
            ),
            'listicle' => array(
                "Looking for the best {$subject}? I've done the research so you don't have to. Here are my top picks.",
                "There are so many options for {$subject} that it can be overwhelming. Let me narrow it down to the absolute best.",
                "After testing and analyzing dozens of options, here are the {$subject} that actually deliver results."
            ),
            'review' => array(
                "Is {$subject} worth it? I've spent time with it and here's my honest assessment.",
                "You've probably heard a lot about {$subject}. But does it live up to the hype? Let me give you the real story.",
                "After extensive testing, I'm ready to share my thoughts on {$subject}. Here's what you need to know before deciding."
            ),
            'story' => array(
                "Let me tell you about my experience with {$subject}. It's been quite a journey, and I've learned some valuable lessons along the way.",
                "When I first encountered {$subject}, I had no idea what I was getting into. Here's how it all unfolded.",
                "This is the story of how {$subject} changed my perspective. I hope my experience helps you on your own journey."
            ),
            'explainer' => array(
                "{$subject} can seem complex at first, but it's actually quite straightforward once you understand the basics.",
                "If you've ever wondered what {$subject} really means, you're not alone. Let me break it down in simple terms.",
                "Understanding {$subject} doesn't require a technical background. Here's a clear explanation anyone can follow."
            ),
            'case_study' => array(
                "This case study examines how {$subject} was implemented and the results that followed. The insights are valuable for anyone in a similar situation.",
                "Let me share a real-world example of {$subject} in action. The outcomes might surprise you.",
                "Here's a detailed look at {$subject} and the impact it had. This case study offers lessons we can all learn from."
            ),
            'comparison' => array(
                "Trying to decide between options for {$subject}? Let's compare them side by side so you can make an informed choice.",
                "When it comes to {$subject}, there are several paths to take. Here's how they stack up against each other.",
                "Choosing the right approach to {$subject} requires careful consideration. Let's examine the pros and cons of each option."
            ),
            'trend' => array(
                "The trends shaping {$subject} are fascinating. Here's what's emerging and what it means for the future.",
                "If you want to stay ahead in {$subject}, you need to understand these trends. They're defining the next few years.",
                "The landscape of {$subject} is shifting. These trends will determine where things are headed."
            ),
            'problem_solution' => array(
                "Struggling with {$subject}? You're not alone. Here's a practical solution that actually works.",
                "Many people face challenges with {$subject}. The good news is there are proven ways to overcome them.",
                "If {$subject} has been causing you frustration, this guide offers clear solutions to get you back on track."
            ),
            'beginner' => array(
                "New to {$subject}? Perfect. This guide is designed specifically for beginners like you.",
                "Starting out with {$subject} can feel overwhelming, but it doesn't have to be. Let's take it step by step.",
                "Welcome to the world of {$subject}! This beginner-friendly guide will help you get started with confidence."
            ),
            'advanced' => array(
                "If you're already familiar with {$subject} basics, it's time to level up. These advanced strategies will take you further.",
                "Ready to push your {$subject} skills to the next level? These techniques are for those who want to excel.",
                "This guide covers advanced aspects of {$subject} that most resources overlook. Let's dive deeper."
            ),
            'quick_tips' => array(
                "Need quick wins with {$subject}? These tips are easy to implement and deliver fast results.",
                "Short on time? These {$subject} tips give you maximum impact with minimum effort.",
                "Here are some quick, actionable tips for {$subject} that you can start using today."
            )
        );
        
        $style_intros = $intros[$style] ?? $intros['guide'];
        return $style_intros[array_rand($style_intros)];
    }
    
    // Get style-specific sections
    private function get_style_sections($style, $subject, $keyword, $num_sections, $location) {
        $all_sections = array();
        
        switch ($style) {
            case 'guide':
                $all_sections = array(
                    array('title' => "Understanding " . $subject, 'content' => "Before diving in, let's clarify what {$subject} really means and why it matters. This foundation will help you make better decisions as you progress."),
                    array('title' => "Getting Started with " . $subject, 'content' => "The first step is always the hardest. Here's how to begin your journey with {$subject} without feeling overwhelmed."),
                    array('title' => "Key Strategies for " . $subject, 'content' => "These proven strategies have helped countless people succeed with {$subject}. Focus on implementing one at a time for best results."),
                    array('title' => "Common Mistakes to Avoid", 'content' => "Learning from others' mistakes saves time. Watch out for these common pitfalls when working with {$subject}."),
                    array('title' => "Best Practices", 'content' => "Follow these industry best practices to maximize your success with {$subject}. They're based on real-world experience."),
                    array('title' => "Tools and Resources", 'content' => "The right tools make everything easier. Here are the essential resources for mastering {$subject}."),
                    array('title' => "Next Steps", 'content' => "Now that you understand the basics, here's how to continue your growth with {$subject}.")
                );
                break;
                
            case 'opinion':
                $all_sections = array(
                    array('title' => "The Current State of " . $subject, 'content' => "Here's where we stand today and why it matters. The current landscape reveals important insights about the future."),
                    array('title' => "Why Most People Get It Wrong", 'content' => "There's a common misconception about {$subject} that needs addressing. Let me explain why it's problematic."),
                    array('title' => "The Real Impact of " . $subject, 'content' => "Beyond the headlines, {$subject} affects more than most realize. Here's the deeper picture."),
                    array('title' => "What Needs to Change", 'content' => "If we want better outcomes with {$subject}, we need to rethink our approach. Here's what I propose."),
                    array('title' => "The Counterargument", 'content' => "To be fair, there are valid points on the other side. Let's address them honestly."),
                    array('title' => "Looking Ahead", 'content' => "The future of {$subject} depends on the choices we make now. Here's what I believe will happen.")
                );
                break;
                
            case 'news':
                $all_sections = array(
                    array('title' => "Breaking: What's New with " . $subject, 'content' => "The latest developments in {$subject} are significant. Here's what's happened recently."),
                    array('title' => "Key Changes", 'content' => "These specific changes will affect how you approach {$subject}. Understanding them is crucial."),
                    array('title' => "Industry Response", 'content' => "How are experts and industry leaders reacting to these developments? Here's the consensus."),
                    array('title' => "What This Means for You", 'content' => "These changes have practical implications. Here's how they might affect your situation."),
                    array('title' => "What to Watch For", 'content' => "Keep an eye on these upcoming developments related to {$subject}. They could be significant.")
                );
                break;
                
            case 'tutorial':
                $all_sections = array(
                    array('title' => "Step 1: Preparation", 'content' => "Before you begin {$subject}, gather these essential items and set up your environment properly."),
                    array('title' => "Step 2: Getting Started", 'content' => "Let's begin with the basics. Follow these initial steps carefully to set a strong foundation."),
                    array('title' => "Step 3: Building Momentum", 'content' => "Now that you've started, here's how to make consistent progress with {$subject}."),
                    array('title' => "Step 4: Advanced Techniques", 'content' => "Ready for more? These advanced techniques will help you excel with {$subject}."),
                    array('title' => "Step 5: Final Steps", 'content' => "Complete your journey with these finishing touches. You're almost there!")
                );
                break;
                
            case 'listicle':
                $all_sections = array(
                    array('title' => "1. The Top Choice", 'content' => "This is the clear winner when it comes to {$subject}. Here's why it stands out from the rest."),
                    array('title' => "2. Best for Beginners", 'content' => "If you're new to {$subject}, this option provides the easiest starting point."),
                    array('title' => "3. Best Value", 'content' => "Looking for the best bang for your buck? This option delivers excellent results without breaking the bank."),
                    array('title' => "4. Most Popular", 'content' => "There's a reason so many people choose this for {$subject}. It consistently delivers."),
                    array('title' => "5. Expert's Choice", 'content' => "Professionals in {$subject} often recommend this option for its reliability."),
                    array('title' => "6. Best for Advanced Users", 'content' => "If you're experienced with {$subject}, this option offers the advanced features you need."),
                    array('title' => "7. Budget-Friendly Option", 'content' => "Great results don't have to be expensive. This affordable option proves it."),
                    array('title' => "8. Most Innovative", 'content' => "This cutting-edge option brings fresh approaches to {$subject}."),
                    array('title' => "9. Best for Teams", 'content' => "Working with others on {$subject}? This collaborative option works best."),
                    array('title' => "10. The Hidden Gem", 'content' => "Not as well-known, but this option deserves more attention for its quality.")
                );
                break;
                
            default:
                $all_sections = array(
                    array('title' => "Understanding " . $subject, 'content' => "Let's start by understanding what {$subject} really means and why it's important."),
                    array('title' => "Key Concepts", 'content' => "These fundamental concepts form the foundation of {$subject}. Master them first."),
                    array('title' => "Practical Applications", 'content' => "Here's how {$subject} applies in real-world situations."),
                    array('title' => "Common Challenges", 'content' => "These are the typical obstacles you'll encounter with {$subject} and how to overcome them."),
                    array('title' => "Expert Insights", 'content' => "What do the experts say about {$subject}? Here's their collective wisdom."),
                    array('title' => "Getting Started", 'content' => "Ready to begin? Here's your practical starting point.")
                );
        }
        
        // Add location section if needed
        if (!empty($location)) {
            $all_sections[] = array(
                'title' => "{$subject} in {$location}",
                'content' => "For those in {$location}, these local factors influence how {$subject} applies to your situation."
            );
        }
        
        return array_slice($all_sections, 0, $num_sections);
    }
    
    // Get style-specific conclusion
    private function get_style_conclusion($style, $subject) {
        $conclusions = array(
            'guide' => "You now have a solid understanding of {$subject}. Start implementing these strategies today and see the results for yourself.",
            'opinion' => "That's my take on {$subject}. Whether you agree or disagree, I hope it made you think. The conversation continues.",
            'news' => "Stay tuned for more updates on {$subject}. The landscape is changing rapidly, and we'll keep you informed.",
            'tutorial' => "Congratulations! You've completed the tutorial on {$subject}. Practice what you've learned and continue improving.",
            'listicle' => "That's our complete list of {$subject} options. Try a few and see which works best for your needs.",
            'review' => "Now you have the full picture on {$subject}. Make your decision based on what matters most to you.",
            'story' => "That's my story with {$subject}. I hope it inspires you on your own journey. Every experience teaches us something valuable.",
            'explainer' => "Now you understand {$subject} in simple terms. Share this knowledge with others who might benefit.",
            'case_study' => "This case study shows what's possible with {$subject}. Apply these lessons to your own situation.",
            'comparison' => "After comparing the options, you can make an informed decision about {$subject}. Choose what aligns with your goals.",
            'trend' => "These trends will continue shaping {$subject}. Stay informed and adapt your approach accordingly.",
            'problem_solution' => "With these solutions, you can overcome challenges with {$subject}. Take action today.",
            'beginner' => "You're no longer a complete beginner with {$subject}. Keep learning and practicing to build your skills.",
            'advanced' => "These advanced strategies give you an edge with {$subject}. Implement them strategically for maximum impact.",
            'quick_tips' => "These quick tips for {$subject} are easy to implement. Start with one and build from there."
        );
        
        return $conclusions[$style] ?? $conclusions['guide'];
    }
    
    // Generate title
    private function generate_title($topic, $style, $location) {
        $subject = $this->extract_subject($topic);
        $style_config = $this->styles[$style] ?? $this->styles['guide'];
        $pattern = $style_config['title_pattern'];
        
        $title = str_replace(array('{subject}', '{year}'), array($subject, gmdate('Y')), $pattern);
        
        if (!empty($location)) {
            $title .= " in {$location}";
        }
        
        return $title;
    }
    
    // Generate meta description
    private function generate_meta_description($topic, $style) {
        $subject = $this->extract_subject($topic);
        
        $metas = array(
            'guide' => "Complete guide to {$subject}. Learn strategies, tips, and best practices for success.",
            'opinion' => "Why {$subject} matters more than you think. Expert insights and analysis.",
            'news' => "Latest news and updates on {$subject}. What you need to know right now.",
            'tutorial' => "Step-by-step tutorial for {$subject}. Easy instructions for beginners and experts.",
            'listicle' => "Top 10 {$subject} options reviewed and ranked. Find the best for your needs.",
            'review' => "Honest review of {$subject}. Pros, cons, and real-world experience.",
            'story' => "Personal journey with {$subject}. Lessons learned and insights gained.",
            'explainer' => "{$subject} explained in simple terms. No jargon, just clear explanations.",
            'case_study' => "Case study on {$subject}. Real results and practical lessons.",
            'comparison' => "{$subject} compared. See the pros and cons side by side.",
            'trend' => "{$subject} trends for " . gmdate('Y') . ". What's shaping the future.",
            'problem_solution' => "Solutions for {$subject} challenges. Practical fixes that work.",
            'beginner' => "{$subject} for beginners. Start here with this easy guide.",
            'advanced' => "Advanced {$subject} strategies. Take your skills to the next level.",
            'quick_tips' => "Quick tips for {$subject}. Fast wins you can implement today."
        );
        
        return $metas[$style] ?? $metas['guide'];
    }
    
    // Extract subject from topic
    private function extract_subject($topic) {
        $topic = trim($topic);
        $prefixes = array('how to ', 'what is ', 'best ', 'top ', 'learn ', 'guide to ', 'the ', 'a ');
        
        $lower = strtolower($topic);
        foreach ($prefixes as $prefix) {
            if (strpos($lower, $prefix) === 0) {
                $topic = substr($topic, strlen($prefix));
                break;
            }
        }
        
        return ucfirst(trim(rtrim($topic, '?!.')));
    }
    
    // Extract keyword
    private function extract_keyword($topic) {
        $subject = $this->extract_subject($topic);
        return strtolower(str_replace(' ', '-', $subject));
    }
    
    
    
    
    // Output saved SEO meta tags on the front end
    public function output_seo_meta() {
        if (!is_singular()) {
            return;
        }

        $post_id = get_queried_object_id();
        if (!$post_id) {
            return;
        }

        $meta_description = get_post_meta($post_id, '_simbe1_meta_description', true);
        if (empty($meta_description)) {
            $meta_description = get_the_excerpt($post_id);
        }
        $meta_description = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $meta_description)));
        $meta_keywords    = get_post_meta($post_id, '_simbe1_meta_keywords', true);

        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '" />' . "\n";
        }
        if (!empty($meta_keywords)) {
            echo '<meta name="keywords" content="' . esc_attr($meta_keywords) . '" />' . "\n";
        }

        $post     = get_post($post_id);
        $og_title = $post ? $post->post_title : '';
        $og_url   = get_permalink($post_id);
        $og_image = get_the_post_thumbnail_url($post_id, 'large');

        echo '<meta property="og:type" content="article" />' . "\n";
        if ($og_title) {
            echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
        }
        if ($meta_description) {
            echo '<meta property="og:description" content="' . esc_attr($meta_description) . '" />' . "\n";
        }
        if ($og_url) {
            echo '<meta property="og:url" content="' . esc_url($og_url) . '" />' . "\n";
        }
        if ($og_image) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
        }
    }

    // Block requests to private/internal hosts (SSRF guard)
    private function is_private_host($host) {
        $ip = gethostbyname($host);
        if ($ip === $host && !filter_var($ip, FILTER_VALIDATE_IP)) {
            return true;
        }
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    // Usage stats from the tracking table
    private function get_usage_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'simbe1_article_tracking';
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        if ($exists !== $table) {
            return array('total' => 0, 'ai' => 0, 'saved' => 0);
        }
        return array(
            'total' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}"), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            'ai'    => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE used_ai = 1"), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            'saved' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE post_id IS NOT NULL AND post_id > 0"), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );
    }

// AJAX: Generate article
    public function ajax_generate_article() {
        check_ajax_referer('simbe1ajax_nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'simbe-ai-article-generator')));
            return;
        }
        
        $topic = sanitize_text_field(wp_unslash($_POST['topic'] ?? ''));
        $style = sanitize_text_field(wp_unslash($_POST['style'] ?? 'guide'));
        $length = sanitize_text_field(wp_unslash($_POST['length'] ?? 'medium'));
        $location = sanitize_text_field(wp_unslash($_POST['location'] ?? ''));
        $source_url = esc_url_raw(wp_unslash($_POST['source_url'] ?? ''));
        
        if (empty($topic)) {
            wp_send_json_error(array('message' => __('Please enter a topic', 'simbe-ai-article-generator')));
            return;
        }
        
        $article = false;
        $used_ai = false;
        
        if ($this->is_ai_enabled()) {
            $article = $this->generate_with_ai($topic, $style, $length, $location);
            if ($article === false && !empty($this->last_ai_error)) {
                wp_send_json_error(array('message' => __('AI Error: ', 'simbe-ai-article-generator') . $this->last_ai_error));
                return;
            }
            $used_ai = ($article !== false);
        }
        
        if (!$article) {
            $title = $this->generate_title($topic, $style, $location);
            $content = $this->generate_with_templates($topic, $style, $length, $location);
            $meta = $this->generate_meta_description($topic, $style);
            
            $article = array(
                'title' => $title,
                'meta_description' => $meta,
                'content' => $content,
                'focus_keyword' => $this->extract_keyword($topic),
                'used_ai' => false
            );
        }
        
        $article['style'] = $style;
        $article['word_count'] = str_word_count(wp_strip_all_tags($article['content']));
        $article['reading_time'] = ceil($article['word_count'] / 200);

        // Log the generation in the tracking table
        global $wpdb;
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'simbe1_article_tracking',
            array(
                'user_id'    => get_current_user_id(),
                'source_url' => $source_url,
                'topic'      => $topic,
                'style'      => $style,
                'used_ai'    => $article['used_ai'] ? 1 : 0,
            ),
            array('%d', '%s', '%s', '%s', '%d')
        );
        $article['tracking_id'] = $inserted ? (int) $wpdb->insert_id : 0;

        wp_send_json_success($article);
    }
    
    public function ajax_fetch_url() {
        check_ajax_referer('simbe1ajax_nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'simbe-ai-article-generator')));
            return;
        }
        
        $url = esc_url_raw(wp_unslash($_POST['url'] ?? ''));
        
        if (empty($url)) {
            wp_send_json_error(array('message' => __('Please enter a valid URL', 'simbe-ai-article-generator')));
            return;
        }

        // SSRF guard: only http/https to public hosts
        $parsed = wp_parse_url($url);
        $scheme = strtolower($parsed['scheme'] ?? '');
        $host   = strtolower($parsed['host'] ?? '');
        if (!in_array($scheme, array('http', 'https'), true) || empty($host) || $this->is_private_host($host)) {
            wp_send_json_error(array('message' => __('URL is not allowed', 'simbe-ai-article-generator')));
            return;
        }
        
        $response = wp_remote_get($url, array('timeout' => 15));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => __('Could not fetch URL', 'simbe-ai-article-generator')));
            return;
        }
        
        $html = wp_remote_retrieve_body($response);
        
        $title = '';
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $title = trim(wp_strip_all_tags($matches[1]));
        }
        
        wp_send_json_success(array('title' => $title));
    }
    
    public function ajax_save_article() {
        check_ajax_referer('simbe1ajax_nonce', 'security');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'simbe-ai-article-generator')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $content = wp_kses_post(wp_unslash($_POST['content'] ?? ''));
        $meta_description = sanitize_text_field(wp_unslash($_POST['meta_description'] ?? ''));
        $location = sanitize_text_field(wp_unslash($_POST['location'] ?? ''));
        $style = sanitize_text_field(wp_unslash($_POST['style'] ?? 'guide'));
        $source_url = esc_url_raw(wp_unslash($_POST['source_url'] ?? ''));
        $cover_id = intval($_POST['cover_id'] ?? 0);
        $meta_keywords = sanitize_text_field(wp_unslash($_POST['meta_keywords'] ?? ''));
        $tracking_id = intval($_POST['tracking_id'] ?? 0);
        
        if (empty($title) || empty($content)) {
            wp_send_json_error(array('message' => __('Title and content are required', 'simbe-ai-article-generator')));
            return;
        }
        
        $post_id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => $content,
            'post_excerpt' => $meta_description,
            'post_status' => 'draft',
            'post_author' => $user_id
        ));
        
        if (is_wp_error($post_id)) {
            wp_send_json_error(array('message' => $post_id->get_error_message()));
            return;
        }
        
        if ($cover_id > 0) {
            set_post_thumbnail($post_id, $cover_id);
        }
        
        update_post_meta($post_id, '_simbe1_meta_description', $meta_description);
        update_post_meta($post_id, '_simbe1_meta_keywords', $meta_keywords);
        update_post_meta($post_id, '_simbe1_location', $location);
        update_post_meta($post_id, '_simbe1_style', $style);
        if (!empty($source_url)) {
            update_post_meta($post_id, '_simbe1_source_url', $source_url);
        }
        
        // Link the tracked generation to the saved post
        if ($tracking_id > 0) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'simbe1_article_tracking',
                array('post_id' => $post_id),
                array('id' => $tracking_id, 'user_id' => $user_id),
                array('%d'),
                array('%d', '%d')
            );
        }
        
        wp_send_json_success(array(
            'message' => __('Article saved as draft!', 'simbe-ai-article-generator'),
            'post_id' => $post_id,
            'edit_url' => get_edit_post_link($post_id)
        ));
    }
    

    
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=simbe1-articles-settings') . '">' . esc_html__('Settings', 'simbe-ai-article-generator') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    // Render admin page
    public function render_admin_page() {
        $api_key = $this->get_api_key();
        ?>
        <div class="wrap simbe-wrap">
            <h1 class="simbe-header"><?php esc_html_e('Article Generator', 'simbe-ai-article-generator'); ?></h1>
            
            <?php if ($this->is_ai_enabled()): ?>
            <div class="simbe-status simbe-status-enabled">
                <span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e('AI Enabled', 'simbe-ai-article-generator'); ?>
            </div>
            <?php else: ?>
            <div class="simbe-status simbe-status-disabled">
                <span class="dashicons dashicons-admin-generic"></span> <?php esc_html_e('Templates Only', 'simbe-ai-article-generator'); ?>
            </div>
            <?php endif; ?>
            
            <?php $usage = $this->get_usage_stats(); ?>
            <div class="simbe-usage-grid">
                <div class="simbe-usage-card">
                    <span class="simbe-usage-value"><?php echo esc_html($usage['total']); ?></span>
                    <span class="simbe-usage-label"><?php esc_html_e('Articles Generated', 'simbe-ai-article-generator'); ?></span>
                </div>
                <div class="simbe-usage-card">
                    <span class="simbe-usage-value"><?php echo esc_html($usage['ai']); ?></span>
                    <span class="simbe-usage-label"><?php esc_html_e('With AI', 'simbe-ai-article-generator'); ?></span>
                </div>
                <div class="simbe-usage-card">
                    <span class="simbe-usage-value"><?php echo esc_html($usage['saved']); ?></span>
                    <span class="simbe-usage-label"><?php esc_html_e('Saved as Drafts', 'simbe-ai-article-generator'); ?></span>
                </div>
            </div>
            
            <div class="simbe-grid">
                <div class="simbe-card">
                    <h2><?php esc_html_e('Generate Article', 'simbe-ai-article-generator'); ?></h2>
                    
                    <div class="simbe-radio-group">
                        <label>
                            <input type="radio" name="input_mode" value="topic" checked onchange="toggleInputMode()"> <?php esc_html_e('Topic/Title', 'simbe-ai-article-generator'); ?>
                        </label>
                        <label>
                            <input type="radio" name="input_mode" value="url" onchange="toggleInputMode()"> <?php esc_html_e('Reference URL', 'simbe-ai-article-generator'); ?>
                        </label>
                    </div>
                    
                    <div id="de-topic-input">
                        <div class="simbe-field">
                            <label class="simbe-label"><?php esc_html_e('Article Topic/Title', 'simbe-ai-article-generator'); ?></label>
                            <input type="text" id="de-topic" class="simbe-input" placeholder="<?php esc_attr_e('e.g., Digital Marketing, How to Learn Python', 'simbe-ai-article-generator'); ?>">
                        </div>
                    </div>
                    
                    <div id="de-url-input" style="display: none;">
                        <div class="simbe-field">
                            <label class="simbe-label"><?php esc_html_e('Reference URL', 'simbe-ai-article-generator'); ?></label>
                            <div class="simbe-url-row">
                                <input type="url" id="de-url" class="simbe-input" placeholder="https://example.com/article">
                                <button type="button" onclick="fetchUrl(this)" class="button"><?php esc_html_e('Fetch', 'simbe-ai-article-generator'); ?></button>
                            </div>
                            <p class="simbe-hint"><?php esc_html_e('Paste a URL to rewrite with unique structure', 'simbe-ai-article-generator'); ?></p>
                        </div>
                        <div id="de-url-preview" class="simbe-url-preview" style="display: none;">
                            <strong id="de-url-title"></strong>
                        </div>
                        <div class="simbe-field">
                            <label class="simbe-label"><?php esc_html_e('Your Topic (to rewrite as)', 'simbe-ai-article-generator'); ?></label>
                            <input type="text" id="de-rewrite-topic" class="simbe-input" placeholder="<?php esc_attr_e('Your version of the topic', 'simbe-ai-article-generator'); ?>">
                        </div>
                    </div>
                    
                    <div class="simbe-field">
                        <label class="simbe-label"><?php esc_html_e('Article Style', 'simbe-ai-article-generator'); ?></label>
                        <select id="de-style" class="simbe-select">
                            <?php foreach ($this->styles as $key => $style): ?>
                            <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($style['name']); ?> - <?php echo esc_html($style['desc']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="simbe-half-grid">
                        <div>
                            <label class="simbe-label"><?php esc_html_e('Length', 'simbe-ai-article-generator'); ?></label>
                            <select id="de-length" class="simbe-select">
                                <option value="short"><?php esc_html_e('Short (~800 words)', 'simbe-ai-article-generator'); ?></option>
                                <option value="medium" selected><?php esc_html_e('Medium (~1500 words)', 'simbe-ai-article-generator'); ?></option>
                                <option value="long"><?php esc_html_e('Long (~2500 words)', 'simbe-ai-article-generator'); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="simbe-label"><?php esc_html_e('Location (GEO)', 'simbe-ai-article-generator'); ?></label>
                            <input type="text" id="de-location" class="simbe-input" placeholder="<?php esc_attr_e('e.g., New York, USA', 'simbe-ai-article-generator'); ?>">
                        </div>
                    </div>
                    
                    <button onclick="generateArticle(this)" class="button button-primary button-large simbe-btn-full">
                        <?php esc_html_e('Generate Article', 'simbe-ai-article-generator'); ?>
                    </button>
                </div>
                
                <div class="simbe-card">
                    <h2><?php esc_html_e('Preview', 'simbe-ai-article-generator'); ?></h2>
                    
                    <div id="de-preview-placeholder" class="simbe-placeholder">
                        <span class="dashicons dashicons-media-document"></span>
                        <p><?php esc_html_e('Generated article will appear here', 'simbe-ai-article-generator'); ?></p>
                    </div>
                    
                    <div id="de-preview-content" style="display: none;">
                        <div class="simbe-field">
                            <label class="simbe-label"><?php esc_html_e('Cover Image', 'simbe-ai-article-generator'); ?></label>
                            <div class="simbe-cover-box">
                                <img id="de-cover-img" class="simbe-cover-img" src="" style="display: none;">
                                <div id="de-cover-placeholder">
                                    <span class="dashicons dashicons-format-image"></span>
                                    <p style="color: #999; font-size: 13px;"><?php esc_html_e('No image selected', 'simbe-ai-article-generator'); ?></p>
                                </div>
                            </div>
                            <div class="simbe-btn-row">
                                <button type="button" onclick="openMediaLibrary()" class="button">
                                    <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span> <?php esc_html_e('Select Image', 'simbe-ai-article-generator'); ?>
                                </button>
                                <button type="button" onclick="removeCover()" class="button" id="de-remove-cover" style="display: none;">
                                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span> <?php esc_html_e('Remove', 'simbe-ai-article-generator'); ?>
                                </button>
                                <input type="hidden" id="de-cover-id" value="">
                            </div>
                        </div>
                        
                        <div class="simbe-field">
                            <label class="simbe-label"><?php esc_html_e('Title', 'simbe-ai-article-generator'); ?></label>
                            <input type="text" id="de-article-title" class="simbe-title-input">
                        </div>
                        
                        <details style="margin-bottom: 20px;" open>
                            <summary style="cursor: pointer; font-weight: 600; padding: 10px; background: #f9f9f9; border-radius: 8px;"><?php esc_html_e('SEO Settings', 'simbe-ai-article-generator'); ?></summary>
                            <div class="simbe-seo-box">
                                <div class="simbe-seo-field">
                                    <label class="simbe-seo-label"><?php esc_html_e('Focus Keyword', 'simbe-ai-article-generator'); ?></label>
                                    <input type="text" id="de-focus-keyword" class="simbe-seo-input">
                                </div>
                                <div class="simbe-seo-field">
                                    <label class="simbe-seo-label"><?php esc_html_e('Meta Description', 'simbe-ai-article-generator'); ?></label>
                                    <textarea id="de-meta-desc" rows="2" class="simbe-seo-input"></textarea>
                                </div>
                                <div>
                                    <label class="simbe-seo-label"><?php esc_html_e('Meta Keywords (comma separated)', 'simbe-ai-article-generator'); ?></label>
                                    <input type="text" id="de-meta-keywords" class="simbe-seo-input">
                                </div>
                            </div>
                        </details>
                        
                        <div class="simbe-field">
                            <label class="simbe-label"><?php esc_html_e('Content', 'simbe-ai-article-generator'); ?></label>
                            <textarea id="de-article-content" class="simbe-content-box" rows="20"></textarea>
                        </div>
                        
                        <button onclick="saveArticle()" class="button button-primary simbe-btn-full">
                            <span class="dashicons dashicons-save" style="vertical-align: middle;"></span> <?php esc_html_e('Save as Draft', 'simbe-ai-article-generator'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        
        <?php
    }
    
    // Render settings
    public function render_settings() {
        $options = get_option('simbe1_articles_options', array());
        $api_key = $this->get_api_key();
        $has_key = !empty($api_key);
        ?>
        <div class="wrap simbe-settings-wrap">
            <div class="simbe-settings-hero <?php echo $has_key ? 'is-connected' : ''; ?>">
                <div class="simbe-settings-hero-icon">
                    <span class="dashicons dashicons-admin-generic"></span>
                </div>
                <div class="simbe-settings-hero-text">
                    <h1><?php esc_html_e('Simbe AI Article Generator', 'simbe-ai-article-generator'); ?></h1>
                    <p><?php esc_html_e('Configure your Groq AI integration to generate smarter, SEO-ready articles.', 'simbe-ai-article-generator'); ?></p>
                </div>
                <div class="simbe-settings-hero-badge <?php echo $has_key ? 'badge-on' : 'badge-off'; ?>">
                    <span class="dashicons <?php echo $has_key ? 'dashicons-yes-alt' : 'dashicons-warning'; ?>"></span>
                    <?php echo $has_key ? esc_html__('Connected', 'simbe-ai-article-generator') : esc_html__('Not Configured', 'simbe-ai-article-generator'); ?>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('simbe1_articles_settings'); ?>

                <div class="simbe-settings-grid">
                    <div class="simbe-settings-card">
                        <div class="simbe-settings-card-icon">
                            <span class="dashicons dashicons-lock"></span>
                        </div>
                        <h2><?php esc_html_e('Groq API Key', 'simbe-ai-article-generator'); ?></h2>
                        <p class="simbe-settings-desc">
                            <?php esc_html_e('Add your Groq API key to enable AI-powered article generation. Free tier includes 14,000 requests/month.', 'simbe-ai-article-generator'); ?>
                        </p>
                        <input type="password" name="simbe1_articles_options[groq_api_key]" value="<?php echo esc_attr($options['groq_api_key'] ?? ''); ?>" class="simbe-api-input" placeholder="gsk_...">
                        <p class="description">
                            <a href="https://console.groq.com" target="_blank" rel="noopener">
                                <?php esc_html_e('Get a free key at console.groq.com', 'simbe-ai-article-generator'); ?>
                            </a>
                        </p>
                        <button type="button" class="button button-secondary simbe-test-btn" id="simbe-test-api">
                            <span class="dashicons dashicons-plugins-checked"></span>
                            <?php esc_html_e('Test Connection', 'simbe-ai-article-generator'); ?>
                        </button>
                        <p class="simbe-test-result" id="simbe-test-result"></p>
                    </div>

                    <div class="simbe-settings-card">
                        <div class="simbe-settings-card-icon">
                            <span class="dashicons dashicons-editor-code"></span>
                        </div>
                        <h2><?php esc_html_e('AI Model', 'simbe-ai-article-generator'); ?></h2>
                        <p class="simbe-settings-desc">
                            <?php esc_html_e('Choose which Groq model is used when generating articles with AI.', 'simbe-ai-article-generator'); ?>
                        </p>
                        <select name="simbe1_articles_options[model]" class="simbe-model-select">
                            <?php foreach ($this->get_models() as $model_id => $model_label): ?>
                            <option value="<?php echo esc_attr($model_id); ?>" <?php selected($options['model'] ?? 'llama-3.1-8b-instant', $model_id); ?>><?php echo esc_html($model_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="simbe-settings-hint">
                            <?php echo esc_html__('Capacity and availability vary by model.', 'simbe-ai-article-generator'); ?>
                        </p>
                    </div>
                </div>

                <?php submit_button(esc_html__('Save Settings', 'simbe-ai-article-generator'), 'primary large simbe-save-btn'); ?>
            </form>
        </div>
        <?php
    }

    // AJAX: Test the Groq API connection
    public function ajax_test_api() {
        check_ajax_referer('simbe1ajax_nonce', 'security');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'simbe-ai-article-generator')));
            return;
        }

        $api_key = $this->get_api_key();
        if (empty($api_key)) {
            wp_send_json_error(array('message' => __('Please enter an API key first and save the settings.', 'simbe-ai-article-generator')));
            return;
        }

        $response = wp_remote_post('https://api.groq.com/openai/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'timeout' => 20,
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
            return;
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = json_decode(wp_remote_retrieve_body($response), true);

        if ($status === 200) {
            wp_send_json_success(array('message' => __('Connection successful. Your API key is valid.', 'simbe-ai-article-generator')));
        } else {
            $msg = $body['error']['message'] ?? sprintf(__('Unexpected response (HTTP %d).', 'simbe-ai-article-generator'), $status);
            wp_send_json_error(array('message' => $msg));
        }
    }
}

new SimbeAI_Article_Generator();