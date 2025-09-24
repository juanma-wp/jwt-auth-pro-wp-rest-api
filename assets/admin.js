/**
 * WordPress REST Auth JWT - Admin JavaScript
 * Simple admin interface for JWT authentication management
 */

jQuery(document).ready(function($) {
    'use strict';

    const wpRestAuthJWT = window.wpRestAuthJWT || {};

    /**
     * Initialize admin functionality
     */
    function init() {
        bindEvents();
        initTooltips();
        validateSettings();
    }

    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Settings form validation
        $('#wp-rest-auth-jwt-settings').on('submit', validateForm);

        // Real-time validation
        $('input[name="wp_rest_auth_jwt_settings[secret_key]"]').on('blur', validateSecretKey);
        $('input[name="wp_rest_auth_jwt_settings[token_expiry]"]').on('blur', validateTokenExpiry);

        // Generate new secret key
        $('#generate-secret-key').on('click', generateSecretKey);

        // Test JWT generation
        $('#test-jwt-generation').on('click', testJWTGeneration);

        // Copy to clipboard functionality
        $('.copy-to-clipboard').on('click', copyToClipboard);

        // Toggle advanced settings
        $('#toggle-advanced-settings').on('click', toggleAdvancedSettings);
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        $('.wp-rest-auth-jwt-tooltip').tooltip({
            position: { my: 'left+10 center', at: 'right center' },
            tooltipClass: 'wp-rest-auth-jwt-tooltip-content'
        });
    }

    /**
     * Validate settings form
     */
    function validateForm(e) {
        let isValid = true;

        if (!validateSecretKey()) isValid = false;
        if (!validateTokenExpiry()) isValid = false;

        if (!isValid) {
            e.preventDefault();
            showNotice('Please fix the validation errors before saving.', 'error');
        }
    }

    /**
     * Validate secret key
     */
    function validateSecretKey() {
        const $input = $('input[name="wp_rest_auth_jwt_settings[secret_key]"]');
        const value = $input.val().trim();

        if (!value) {
            showFieldError($input, 'Secret key is required');
            return false;
        }

        if (value.length < 32) {
            showFieldError($input, 'Secret key must be at least 32 characters');
            return false;
        }

        clearFieldError($input);
        return true;
    }

    /**
     * Validate token expiry
     */
    function validateTokenExpiry() {
        const $input = $('input[name="wp_rest_auth_jwt_settings[token_expiry]"]');
        const value = parseInt($input.val());

        if (isNaN(value) || value < 300) {
            showFieldError($input, 'Token expiry must be at least 300 seconds (5 minutes)');
            return false;
        }

        if (value > 86400) {
            showFieldError($input, 'Token expiry should not exceed 86400 seconds (24 hours) for security');
            return false;
        }

        clearFieldError($input);
        return true;
    }

    /**
     * Generate new secret key
     */
    function generateSecretKey(e) {
        e.preventDefault();

        if (!confirm('Generate a new secret key? This will invalidate all existing tokens.')) {
            return;
        }

        const secretKey = generateRandomString(64);
        $('input[name="wp_rest_auth_jwt_settings[secret_key]"]').val(secretKey);
        showNotice('New secret key generated. Remember to save your settings.', 'info');
    }

    /**
     * Test JWT generation
     */
    function testJWTGeneration(e) {
        e.preventDefault();

        const $button = $(this);
        const originalText = $button.text();

        $button.prop('disabled', true).text('Testing...');

        $.ajax({
            url: wpRestAuthJWT.restUrl + 'wp-rest-auth-jwt/v1/test',
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wpRestAuthJWT.nonce);
            }
        }).done(function(response) {
            if (response.success) {
                showNotice('JWT generation test successful!', 'success');
                $('#test-results').html(`
                    <h4>Test Results:</h4>
                    <p><strong>Token Type:</strong> ${response.data.token_type}</p>
                    <p><strong>Expires In:</strong> ${response.data.expires_in} seconds</p>
                    <p><strong>Token Length:</strong> ${response.data.token.length} characters</p>
                `);
            } else {
                showNotice('JWT generation test failed: ' + response.message, 'error');
            }
        }).fail(function(xhr) {
            showNotice('JWT generation test failed: ' + xhr.responseText, 'error');
        }).always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    }

    /**
     * Copy to clipboard
     */
    function copyToClipboard(e) {
        e.preventDefault();

        const $button = $(this);
        const targetSelector = $button.data('target');
        const $target = $(targetSelector);

        if ($target.length) {
            $target.select();
            document.execCommand('copy');

            const originalText = $button.text();
            $button.text('Copied!').addClass('copied');

            setTimeout(() => {
                $button.text(originalText).removeClass('copied');
            }, 2000);
        }
    }

    /**
     * Toggle advanced settings
     */
    function toggleAdvancedSettings(e) {
        e.preventDefault();

        const $advanced = $('.wp-rest-auth-jwt-advanced-settings');
        const $button = $(this);

        if ($advanced.is(':visible')) {
            $advanced.slideUp();
            $button.text('Show Advanced Settings');
        } else {
            $advanced.slideDown();
            $button.text('Hide Advanced Settings');
        }
    }

    /**
     * Show field error
     */
    function showFieldError($field, message) {
        clearFieldError($field);
        $field.addClass('error').after(`<div class="field-error">${message}</div>`);
    }

    /**
     * Clear field error
     */
    function clearFieldError($field) {
        $field.removeClass('error').next('.field-error').remove();
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type = 'info') {
        const $notice = $(`
            <div class="notice notice-${type} is-dismissible">
                <p>${message}</p>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">Dismiss this notice.</span>
                </button>
            </div>
        `);

        $('.wp-header-end').after($notice);

        // Auto dismiss after 5 seconds
        setTimeout(() => {
            $notice.fadeOut(() => $notice.remove());
        }, 5000);
    }

    /**
     * Generate random string
     */
    function generateRandomString(length) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }

    /**
     * Validate all settings on page load
     */
    function validateSettings() {
        setTimeout(() => {
            validateSecretKey();
            validateTokenExpiry();
        }, 100);
    }

    // Initialize when document is ready
    init();
});