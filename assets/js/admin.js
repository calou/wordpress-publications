(function($) {
    'use strict';

    var WPPublications = {
        init: function() {
            this.bindEvents();
            this.initSelect2();
        },

        bindEvents: function() {
            $('#wp-publications-import-form').on('submit', this.handleImportSubmit.bind(this));
        },

        initSelect2: function() {
            if ($.fn.select2) {
                $('.wp-publications-select2').select2({
                    placeholder: wpPublications.strings.selectTags,
                    allowClear: true,
                    width: '100%'
                });
            }
        },

        handleImportSubmit: function(e) {
            e.preventDefault();

            var $form = $('#wp-publications-import-form');
            var $submitBtn = $('#wp-publications-import-btn');
            var $progress = $('#wp-publications-progress');
            var $progressBar = $('#wp-publications-progress-bar');
            var $progressText = $('#wp-publications-progress-text');
            var $resultsTable = $('#wp-publications-results-table');
            var $resultsBody = $('#wp-publications-results-body');

            // Disable form
            $submitBtn.prop('disabled', true);
            $form.find('textarea, select').prop('disabled', true);

            // Show progress
            $progress.show();
            $progressBar.css('width', '0%');
            $progressText.text(wpPublications.strings.importing);
            $resultsTable.hide();
            $resultsBody.empty();

            // Get form data
            var dois = $('#wp-publications-dois').val();
            var tags = $('#wp-publications-tags').val() || [];

            // Initialize import
            $.ajax({
                url: wpPublications.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_publications_import',
                    nonce: wpPublications.nonce,
                    dois: dois,
                    tags: tags
                },
                success: function(response) {
                    if (response.success) {
                        WPPublications.processImport(response.data);
                    } else {
                        WPPublications.showError(response.data.message);
                        WPPublications.resetForm();
                    }
                },
                error: function() {
                    WPPublications.showError(wpPublications.strings.error);
                    WPPublications.resetForm();
                }
            });
        },

        processImport: function(data) {
            var self = this;
            var dois = data.dois;
            var total = data.total;
            var tagIds = data.tag_ids;
            var delay = data.delay;
            var mailto = data.mailto;
            var current = 0;

            var $progressBar = $('#wp-publications-progress-bar');
            var $progressText = $('#wp-publications-progress-text');
            var $resultsTable = $('#wp-publications-results-table');
            var $resultsBody = $('#wp-publications-results-body');

            $resultsTable.show();

            function importNext() {
                if (current >= dois.length) {
                    self.importComplete();
                    return;
                }

                var doi = dois[current];
                var progress = Math.round(((current + 1) / total) * 100);

                $progressBar.css('width', progress + '%');
                $progressText.text('Importing ' + (current + 1) + ' of ' + total + ': ' + doi);

                $.ajax({
                    url: wpPublications.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'wp_publications_import_single',
                        nonce: wpPublications.nonce,
                        doi: doi,
                        mailto: mailto,
                        tag_ids: tagIds
                    },
                    success: function(response) {
                        if (response.success) {
                            self.addResultRow(response.data, true);
                        } else {
                            self.addResultRow({
                                doi: doi,
                                title: '-',
                                message: response.data.message
                            }, false);
                        }
                    },
                    error: function() {
                        self.addResultRow({
                            doi: doi,
                            title: '-',
                            message: wpPublications.strings.error
                        }, false);
                    },
                    complete: function() {
                        current++;
                        // Apply delay before next request
                        setTimeout(importNext, delay);
                    }
                });
            }

            importNext();
        },

        addResultRow: function(data, success) {
            var $resultsBody = $('#wp-publications-results-body');
            var statusClass = success ? 'success' : 'error';
            var statusText = success ? (data.action === 'updated' ? 'Updated' : 'Created') : 'Failed';
            var actions = '';

            if (success && data.edit_url) {
                actions = '<a href="' + data.edit_url + '" target="_blank">Edit</a>';
                if (data.view_url) {
                    actions += ' | <a href="' + data.view_url + '" target="_blank">View</a>';
                }
            } else if (!success) {
                actions = '<span class="error-message">' + (data.message || wpPublications.strings.error) + '</span>';
            }

            var row = '<tr class="' + statusClass + '">' +
                '<td><code>' + this.escapeHtml(data.doi) + '</code></td>' +
                '<td>' + this.escapeHtml(data.title || '-') + '</td>' +
                '<td><span class="status-' + statusClass + '">' + statusText + '</span></td>' +
                '<td>' + actions + '</td>' +
                '</tr>';

            $resultsBody.append(row);
        },

        importComplete: function() {
            var $progressText = $('#wp-publications-progress-text');
            $progressText.html('<strong>' + wpPublications.strings.success + '</strong>');
            this.resetForm();
        },

        showError: function(message) {
            var $progressText = $('#wp-publications-progress-text');
            $progressText.html('<span class="error">' + this.escapeHtml(message) + '</span>');
        },

        resetForm: function() {
            var $form = $('#wp-publications-import-form');
            var $submitBtn = $('#wp-publications-import-btn');

            $submitBtn.prop('disabled', false);
            $form.find('textarea, select').prop('disabled', false);
        },

        escapeHtml: function(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    $(document).ready(function() {
        WPPublications.init();
    });

})(jQuery);
