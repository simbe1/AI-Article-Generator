/**
 * DigitalEdu AI Article Generator JavaScript
 */
(function($) {
    'use strict';

    const DEArticle = {
        isGenerating: false,

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('#de-generate-btn').on('click', $.proxy(this.generateArticle, this));
            $('#de-save-btn').on('click', $.proxy(this.saveArticle, this));
            $('#de-publish-btn').on('click', $.proxy(this.publishArticle, this));
        },

        generateArticle: function() {
            if (this.isGenerating) return;

            const topic = $('#de-topic').val().trim();
            if (!topic) {
                this.showError('Please enter a topic');
                return;
            }

            const data = {
                action: 'de_generate_article',
                nonce: deArticle.nonce,
                topic: topic,
                tone: $('#de-tone').val(),
                length: $('#de-length').val(),
                location: $('#de-location').val(),
                include_schema: $('#de-include-schema').is(':checked'),
                include_faq: $('#de-include-faq').is(':checked')
            };

            this.setLoading(true);

            $.ajax({
                url: deArticle.ajaxUrl,
                type: 'POST',
                data: data,
                success: $.proxy(this.handleSuccess, this),
                error: $.proxy(this.handleError, this)
            });
        },

        handleSuccess: function(response) {
            this.setLoading(false);

            if (!response.success) {
                this.showError(response.data.message || 'Generation failed');
                return;
            }

            const data = response.data;
            
            // Show provider info
            const providerNames = {
                'ollama': 'Ollama (Local)',
                'groq': 'Groq API',
                'huggingface': 'Hugging Face',
                'cohere': 'Cohere',
                'local': 'Template Generator'
            };
            const provider = providerNames[data.provider] || data.provider;
            $('#de-generation-status').text('Generated with: ' + provider).css('color', '#4caf50');
            
            this.displayArticle(data);
            this.showSeoAnalysis(data);
            this.showSaveCard(data);
        },

        handleError: function(xhr, status, error) {
            this.setLoading(false);
            this.showError('Network error: ' + error);
        },

        displayArticle: function(data) {
            let html = '<div class="de-article-preview">';
            
            if (data.title) {
                html += '<h1 class="de-article-title">' + this.escapeHtml(data.title) + '</h1>';
            }
            
            if (data.meta_description) {
                html += '<p class="de-meta-desc"><strong>Meta Description:</strong> ' + this.escapeHtml(data.meta_description) + '</p>';
            }
            
            html += '<div class="de-article-meta">';
            html += '<span><i class="fas fa-file-word"></i> ' + (data.word_count || 0) + ' words</span>';
            html += '<span><i class="fas fa-clock"></i> ' + (data.reading_time || '0 min') + '</span>';
            if (data.focus_keyword) {
                html += '<span><i class="fas fa-key"></i> ' + this.escapeHtml(data.focus_keyword) + '</span>';
            }
            html += '</div>';
            
            html += '<div class="de-article-body">' + data.content + '</div>';
            
            if (data.faq && data.faq.length > 0) {
                html += '<div class="de-article-faq"><h2>Frequently Asked Questions</h2>';
                data.faq.forEach(function(item) {
                    html += '<div class="de-faq-item">';
                    html += '<h3>' + this.escapeHtml(item.question) + '</h3>';
                    html += '<p>' + this.escapeHtml(item.answer) + '</p>';
                    html += '</div>';
                }.bind(this));
                html += '</div>';
            }
            
            html += '</div>';
            
            $('#de-preview-content').html(html);
            
            $('#de-article-title').val(data.title || '');
            $('#de-article-content').val(data.content || '');
            $('#de-article-meta').val(JSON.stringify({
                meta_description: data.meta_description,
                focus_keyword: data.focus_keyword,
                secondary_keywords: data.secondary_keywords,
                word_count: data.word_count,
                reading_time: data.reading_time
            }));
            $('#de-article-schema').val(data.schema_markup || '');
        },

        showSeoAnalysis: function(data) {
            const analysis = $('#de-seo-analysis');
            const stats = $('#de-seo-stats');
            
            let keywords = [];
            if (data.focus_keyword) keywords.push(data.focus_keyword);
            if (data.secondary_keywords) keywords = keywords.concat(data.secondary_keywords.slice(0, 4));
            
            let html = '';
            html += '<div class="de-seo-stat">';
            html += '<div class="de-seo-stat-value">' + (data.word_count || 0) + '</div>';
            html += '<div class="de-seo-stat-label">Words</div>';
            html += '</div>';
            html += '<div class="de-seo-stat">';
            html += '<div class="de-seo-stat-value">' + keywords.length + '</div>';
            html += '<div class="de-seo-stat-label">Keywords</div>';
            html += '</div>';
            html += '<div class="de-seo-stat">';
            html += '<div class="de-seo-stat-value">' + (data.faq ? data.faq.length : 0) + '</div>';
            html += '<div class="de-seo-stat-label">FAQ Items</div>';
            html += '</div>';
            
            stats.html(html);
            analysis.show();
        },

        showSaveCard: function(data) {
            $('#de-save-card').slideDown();
            $('html, body').animate({ scrollTop: 0 }, 300);
        },

        saveArticle: function() {
            this.submitArticle('draft');
        },

        publishArticle: function() {
            this.submitArticle('publish');
        },

        submitArticle: function(status) {
            const title = $('#de-article-title').val();
            const content = $('#de-article-content').val();
            
            if (!title || !content) {
                this.showError('Please generate an article first');
                return;
            }

            const meta = JSON.parse($('#de-article-meta').val() || '{}');
            
            const data = {
                action: 'de_save_article',
                nonce: deArticle.nonce,
                title: title,
                content: content,
                meta_description: meta.meta_description || '',
                focus_keyword: meta.focus_keyword || '',
                schema_markup: $('#de-article-schema').val() || '',
                category: $('#de-article-category').val(),
                tags: $('#de-article-tags').val(),
                post_status: status
            };

            $.ajax({
                url: deArticle.ajaxUrl,
                type: 'POST',
                data: data,
                success: $.proxy(function(response) {
                    if (response.success) {
                        this.showSuccess('Article saved successfully!');
                        setTimeout(function() {
                            window.location.href = response.data.edit_url;
                        }, 1500);
                    } else {
                        this.showError(response.data.message || 'Save failed');
                    }
                }, this),
                error: $.proxy(function() {
                    this.showError('Network error');
                }, this)
            });
        },

        setLoading: function(loading) {
            this.isGenerating = loading;
            const btn = $('#de-generate-btn');
            const status = $('#de-generation-status');
            
            if (loading) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Generating...');
                status.text('Connecting to AI provider...').css('color', '#666');
            } else {
                btn.prop('disabled', false).html('<i class="fas fa-magic"></i> Generate Article');
                status.text('');
            }
        },

        showError: function(message) {
            this.showNotification(message, 'error');
        },

        showSuccess: function(message) {
            this.showNotification(message, 'success');
        },

        showNotification: function(message, type) {
            const notification = $('<div class="de-notification de-notification-' + type + '">' + message + '</div>');
            $('body').append(notification);
            notification.fadeIn();
            setTimeout(function() {
                notification.fadeOut(function() { $(this).remove(); });
            }, 3000);
        },

        escapeHtml: function(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };

    $(document).ready(function() {
        DEArticle.init();
    });

})(jQuery);
