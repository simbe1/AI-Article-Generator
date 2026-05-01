/**
 * Simbe AI Article Generator - Admin JavaScript
 */
(function($) {
    'use strict';

    var generatedArticle = null;
    var fetchedUrl = '';

    window.toggleInputMode = function() {
        var mode = document.querySelector('input[name="input_mode"]:checked').value;
        document.getElementById('de-topic-input').style.display = mode === 'topic' ? 'block' : 'none';
        document.getElementById('de-url-input').style.display = mode === 'url' ? 'block' : 'none';
    };

    window.fetchUrl = function() {
        var url = document.getElementById('de-url').value;
        if (!url) {
            alert('Please enter a URL');
            return;
        }

        var btn = event.target;
        btn.disabled = true;
        btn.textContent = 'Fetching...';

        $.post(simbe1Ajax.ajaxurl, {
            action: 'simbe1_fetch_url',
            security: simbe1Ajax.nonce,
            url: url
        }, function(response) {
            btn.disabled = false;
            btn.textContent = 'Fetch';

            if (response.success) {
                fetchedUrl = url;
                document.getElementById('de-url-title').textContent = response.data.title;
                document.getElementById('de-url-preview').style.display = 'block';
                document.getElementById('de-rewrite-topic').value = response.data.title;
            } else {
                alert(response.data.message);
            }
        });
    };

    window.generateArticle = function() {
        var mode = document.querySelector('input[name="input_mode"]:checked').value;
        var topic = mode === 'url' ? document.getElementById('de-rewrite-topic').value : document.getElementById('de-topic').value;

        if (!topic) {
            alert('Please enter a topic');
            return;
        }

        var btn = event.target;
        btn.disabled = true;
        btn.innerHTML = 'Generating...';

        $.post(simbe1Ajax.ajaxurl, {
            action: 'simbe1_generate_article',
            security: simbe1Ajax.nonce,
            topic: topic,
            style: document.getElementById('de-style').value,
            length: document.getElementById('de-length').value,
            location: document.getElementById('de-location').value,
            source_url: mode === 'url' ? fetchedUrl : ''
        }, function(response) {
            btn.disabled = false;
            btn.innerHTML = 'Generate Article';

            if (response.success) {
                generatedArticle = response.data;
                document.getElementById('de-preview-placeholder').style.display = 'none';
                document.getElementById('de-preview-content').style.display = 'block';
                document.getElementById('de-article-title').value = response.data.title;
                document.getElementById('de-meta-desc').value = response.data.meta_description;
                document.getElementById('de-focus-keyword').value = response.data.focus_keyword || '';
                document.getElementById('de-article-content').innerHTML = response.data.content;
            } else {
                alert(response.data.message);
            }
        });
    };

    window.openMediaLibrary = function() {
        var frame = wp.media({
            title: 'Select Cover Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            document.getElementById('de-cover-id').value = attachment.id;
            document.getElementById('de-cover-img').src = attachment.url;
            document.getElementById('de-cover-img').style.display = 'block';
            document.getElementById('de-cover-placeholder').style.display = 'none';
            document.getElementById('de-remove-cover').style.display = 'inline-block';
        });

        frame.open();
    };

    window.removeCover = function() {
        document.getElementById('de-cover-id').value = '';
        document.getElementById('de-cover-img').src = '';
        document.getElementById('de-cover-img').style.display = 'none';
        document.getElementById('de-cover-placeholder').style.display = 'flex';
        document.getElementById('de-remove-cover').style.display = 'none';
    };

    window.saveArticle = function() {
        if (!generatedArticle) return;

        $.post(simbe1Ajax.ajaxurl, {
            action: 'simbe1_save_article',
            security: simbe1Ajax.nonce,
            title: document.getElementById('de-article-title').value,
            content: generatedArticle.content,
            meta_description: document.getElementById('de-meta-desc').value,
            meta_keywords: document.getElementById('de-meta-keywords').value,
            location: document.getElementById('de-location').value,
            style: document.getElementById('de-style').value,
            source_url: fetchedUrl,
            cover_id: document.getElementById('de-cover-id').value
        }, function(response) {
            if (response.success) {
                alert('Article saved as draft!');
                window.open(response.data.edit_url, '_blank');
                location.reload();
            } else {
                alert(response.data.message);
            }
        });
    };

})(jQuery);
