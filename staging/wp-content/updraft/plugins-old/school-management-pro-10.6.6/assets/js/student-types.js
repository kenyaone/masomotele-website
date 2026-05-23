/*
 * School Management Pro - Student Types JavaScript
 * Modern, modular JavaScript for student types management
 */

(function($) {
    'use strict';

    // Student Types Management Object
    window.WLSMStudentTypes = {
        
        // Configuration
        config: {
            formSelector: '#wlsm-save-student-type-form',
            tableSelector: '#wlsm-class-student-type-table',
            submitButtonSelector: '#wlsm-save-student-type-btn',
            labelInputSelector: '#wlsm_student_type_label',
            successAlertSelector: '#student-type-success-alert',
            errorAlertSelector: '#student-type-error-alert'
        },

        // Initialize the module
        init: function() {
            console.log('WLSMStudentTypes: Initializing...');
            
            this.setupForm();
            this.setupDataTable();
            this.setupQuickAdd();
            this.setupValidation();
            
            console.log('WLSMStudentTypes: Initialization complete');
        },

        // Setup form submission
        setupForm: function() {
            const self = this;
            const $form = $(this.config.formSelector);
            
            if ($form.length === 0) {
                console.log('Student type form not found');
                return;
            }

            // Setup ajaxForm for submission
            $form.ajaxForm({
                dataType: 'json',
                beforeSubmit: function(arr, $form, options) {
                    console.log('Form submission started');
                    self.showLoading();
                    self.clearMessages();
                    
                    // Basic validation
                    const label = $form.find('input[name="label"]').val().trim();
                    if (!label) {
                        self.showError('Please enter a student type name.');
                        self.hideLoading();
                        return false;
                    }
                    
                    if (label.length > 191) {
                        self.showError('Student type name cannot exceed 191 characters.');
                        self.hideLoading();
                        return false;
                    }
                    
                    return true;
                },
                success: function(response) {
                    console.log('Form submission response:', response);
                    self.hideLoading();
                    
                    if (response.success) {
                        self.showSuccess(response.data.message || 'Student type saved successfully!');
                        
                        // Reset form if it's a new addition
                        if (response.data.reset) {
                            self.resetForm();
                        }
                        
                        // Refresh DataTable if it exists
                        self.refreshDataTable();
                        
                        // Auto-hide success message after 3 seconds
                        setTimeout(function() {
                            self.hideMessage('success');
                        }, 3000);
                        
                    } else {
                        // Handle validation errors
                        if (response.data && typeof response.data === 'object') {
                            const errors = Object.values(response.data).join(' ');
                            self.showError(errors);
                        } else {
                            self.showError(response.data || 'An error occurred while saving.');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Form submission error:', xhr.responseText, error);
                    self.hideLoading();
                    
                    let errorMessage = 'Network error. Please try again.';
                    
                    // Try to parse error response
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.data) {
                            errorMessage = response.data;
                        }
                    } catch (e) {
                        // Use default error message
                    }
                    
                    self.showError(errorMessage);
                }
            });
        },

        // Setup DataTable
        setupDataTable: function() {
            const $table = $(this.config.tableSelector);
            
            if ($table.length === 0) {
                console.log('Student types table not found');
                return;
            }

            // Initialize DataTable
            this.dataTable = $table.DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wlsm-fetch-class-student-type'
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'label', name: 'label' },
                    { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
                ],
                language: {
                    processing: '<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>',
                    emptyTable: 'No student types found. Add your first student type using the form.',
                    zeroRecords: 'No matching student types found.',
                    search: 'Search student types:',
                    lengthMenu: 'Show _MENU_ student types per page',
                    info: 'Showing _START_ to _END_ of _TOTAL_ student types',
                    paginate: {
                        first: 'First',
                        last: 'Last',
                        next: 'Next',
                        previous: 'Previous'
                    }
                },
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                order: [[1, 'asc']],
                drawCallback: function(settings) {
                    // Re-initialize any tooltips or other UI elements
                    $('[data-toggle="tooltip"]').tooltip();
                }
            });

            console.log('DataTable initialized for student types');
        },

        // Setup quick add functionality
        setupQuickAdd: function() {
            const self = this;
            
            // Quick add buttons
            $(document).on('click', '.quick-add-type', function(e) {
                e.preventDefault();
                
                const type = $(this).data('type');
                const $input = $(self.config.labelInputSelector);
                
                if (type && $input.length) {
                    $input.val(type).focus();
                    
                    // Add visual feedback
                    $(this).addClass('btn-success').removeClass('btn-outline-info');
                    setTimeout(function() {
                        $(this).removeClass('btn-success').addClass('btn-outline-info');
                    }.bind(this), 1000);
                    
                    console.log('Quick added type:', type);
                }
            });
        },

        // Setup form validation
        setupValidation: function() {
            const self = this;
            const $labelInput = $(this.config.labelInputSelector);
            
            // Real-time validation
            $labelInput.on('input', function() {
                const value = $(this).val().trim();
                const $formGroup = $(this).closest('.form-group');
                
                // Remove previous validation classes
                $formGroup.removeClass('has-error has-success');
                $(this).removeClass('is-invalid is-valid');
                $formGroup.find('.invalid-feedback, .valid-feedback').remove();
                
                if (value.length === 0) {
                    return; // Don't show validation for empty field
                }
                
                if (value.length > 191) {
                    $(this).addClass('is-invalid');
                    $formGroup.addClass('has-error');
                    $formGroup.append('<div class="invalid-feedback">Maximum 191 characters allowed.</div>');
                } else if (value.length >= 2) {
                    $(this).addClass('is-valid');
                    $formGroup.addClass('has-success');
                    $formGroup.append('<div class="valid-feedback">Looks good!</div>');
                }
            });

            // Character counter
            $labelInput.on('input', function() {
                const current = $(this).val().length;
                const max = 191;
                const remaining = max - current;
                
                let $counter = $(this).siblings('.character-counter');
                if ($counter.length === 0) {
                    $counter = $('<small class="character-counter form-text"></small>');
                    $(this).after($counter);
                }
                
                $counter.text(`${current}/${max} characters`);
                
                if (remaining < 20) {
                    $counter.removeClass('text-muted text-success').addClass('text-warning');
                } else if (remaining < 0) {
                    $counter.removeClass('text-muted text-warning').addClass('text-danger');
                } else {
                    $counter.removeClass('text-warning text-danger').addClass('text-muted');
                }
            });
        },

        // Show loading state
        showLoading: function() {
            const $button = $(this.config.submitButtonSelector);
            $button.addClass('loading').prop('disabled', true);
        },

        // Hide loading state
        hideLoading: function() {
            const $button = $(this.config.submitButtonSelector);
            $button.removeClass('loading').prop('disabled', false);
        },

        // Show success message
        showSuccess: function(message) {
            const $alert = $(this.config.successAlertSelector);
            $alert.find('#success-message').text(message);
            $alert.fadeIn();
        },

        // Show error message
        showError: function(message) {
            const $alert = $(this.config.errorAlertSelector);
            $alert.find('#error-message').text(message);
            $alert.fadeIn();
        },

        // Clear all messages
        clearMessages: function() {
            $(this.config.successAlertSelector).hide();
            $(this.config.errorAlertSelector).hide();
        },

        // Hide specific message type
        hideMessage: function(type) {
            if (type === 'success') {
                $(this.config.successAlertSelector).fadeOut();
            } else if (type === 'error') {
                $(this.config.errorAlertSelector).fadeOut();
            }
        },

        // Reset form
        resetForm: function() {
            const $form = $(this.config.formSelector);
            $form[0].reset();
            
            // Clear validation states
            $form.find('.form-group').removeClass('has-error has-success');
            $form.find('.form-control').removeClass('is-invalid is-valid');
            $form.find('.invalid-feedback, .valid-feedback, .character-counter').remove();
            
            // Focus on first input
            $form.find('input:first').focus();
        },

        // Refresh DataTable
        refreshDataTable: function() {
            if (this.dataTable) {
                this.dataTable.ajax.reload(null, false); // Don't reset paging
                console.log('DataTable refreshed');
            }
        },

        // Utility method to delete student type
        deleteStudentType: function(id, name) {
            const self = this;
            
            if (!confirm(`Are you sure you want to delete the student type "${name}"? This action cannot be undone.`)) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wlsm-delete-student-type',
                    student_type_id: id,
                    ['delete-student-type-' + id]: $('input[name="delete-student-type-' + id + '"]').val()
                },
                success: function(response) {
                    if (response.success) {
                        self.showSuccess(response.data.message || 'Student type deleted successfully!');
                        self.refreshDataTable();
                        
                        setTimeout(function() {
                            self.hideMessage('success');
                        }, 3000);
                    } else {
                        self.showError(response.data || 'Failed to delete student type.');
                    }
                },
                error: function() {
                    self.showError('Network error. Please try again.');
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize if we're on the student types page
        if ($('.wlsm-student-types-page').length > 0) {
            WLSMStudentTypes.init();
        }
    });

    // Make deleteStudentType globally accessible for inline onclick handlers
    window.deleteStudentType = function(id, name) {
        WLSMStudentTypes.deleteStudentType(id, name);
    };

})(jQuery);
