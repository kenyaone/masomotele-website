/*
 * School Management Pro - Setup Wizard JavaScript
 * Version: 2.0.0 - Refactored and improved
 */

(function($) {
    'use strict';

    // Setup Wizard Main Object
    window.wlsmSetupWizard = {
        showLoading: function() {
            console.log('wlsmSetupWizard.showLoading called');
            $('#wlsm-setup-loading-modal').modal('show');
        },
        
        hideLoading: function() {
            console.log('wlsmSetupWizard.hideLoading called');
            $('#wlsm-setup-loading-modal').modal('hide');
        },
        
        showSuccess: function(message) {
            console.log('wlsmSetupWizard.showSuccess called with:', message);
            this.hideLoading();
            
            // Check if toastr is available
            if (typeof toastr !== 'undefined') {
                console.log('Using toastr for success message');
                toastr.success(message || 'Operation completed successfully!');
            } else {
                console.log('toastr not available, using alert for success');
                alert(message || 'Operation completed successfully!');
            }
        },
        
        showError: function(message) {
            console.log('wlsmSetupWizard.showError called with:', message);
            this.hideLoading();
            
            // Check if toastr is available
            if (typeof toastr !== 'undefined') {
                console.log('Using toastr for error message');
                toastr.error(message || 'An error occurred. Please try again.');
            } else {
                console.log('toastr not available, using alert for error');
                alert(message || 'An error occurred. Please try again.');
            }
        },
        
        showInfo: function(message) {
            console.log('wlsmSetupWizard.showInfo called with:', message);
            if (typeof toastr !== 'undefined') {
                toastr.info(message);
            } else {
                alert(message);
            }
        },
        
        showWarning: function(message) {
            console.log('wlsmSetupWizard.showWarning called with:', message);
            if (typeof toastr !== 'undefined') {
                toastr.warning(message);
            } else {
                alert(message);
            }
        }
    };

    // Setup Wizard Utilities
    window.WLSMSetupUtils = {
        
        // Initialize subjects step with improved functionality
        initSubjectsStep: function() {
            console.log('initSubjectsStep called');

            // Add/Remove Subject handlers
            this.initSubjectManagement();
            
            // Quick add functionality
            this.initQuickAddFunctionality();
            
            // Subject count updates
            this.updateAllSubjectCounts();
            
            // Next button handler
            this.initSubjectsNextButton();
        },

        initSubjectManagement: function() {
            // Add subject button
            $(document).off('click.subjects', '.add-subject-btn')
                      .on('click.subjects', '.add-subject-btn', function(e) {
                e.preventDefault();
                const classId = $(this).data('class-id');
                window.WLSMSetupUtils.addSubjectRow(classId);
            });

            // Remove subject button
            $(document).off('click.subjects', '.remove-subject')
                      .on('click.subjects', '.remove-subject', function(e) {
                e.preventDefault();
                const $subjectItem = $(this).closest('.subject-item');
                const classId = $subjectItem.closest('.subjects-container').data('class-id');
                window.WLSMSetupUtils.removeSubjectRow($subjectItem, classId);
            });

            // Clear all subjects
            $(document).off('click.subjects', '.clear-all-subjects')
                      .on('click.subjects', '.clear-all-subjects', function(e) {
                e.preventDefault();
                const classId = $(this).data('class-id');
                if (confirm('Are you sure you want to clear all subjects for this class?')) {
                    window.WLSMSetupUtils.clearAllSubjects(classId);
                }
            });

            // Auto-update subject count when input values change
            $(document).off('keyup.subjects change.subjects', '.subject-name')
                      .on('keyup.subjects change.subjects', '.subject-name', function() {
                const classId = $(this).data('class-id');
                window.WLSMSetupUtils.updateSubjectCount(classId);
                
                // Auto-add new row if this is the last row and has content
                const $container = $(this).closest('.subjects-container');
                const $allInputs = $container.find('.subject-name');
                const $lastInput = $allInputs.last();
                
                if ($(this).is($lastInput) && $(this).val().trim() !== '') {
                    window.WLSMSetupUtils.addSubjectRow(classId);
                }
            });
        },

        initQuickAddFunctionality: function() {
            // Quick add all classes
            $(document).off('click.subjects', '.quick-add-all')
                      .on('click.subjects', '.quick-add-all', function(e) {
                e.preventDefault();
                const subjectName = $(this).data('subject');
                console.log('Quick adding subject to all classes:', subjectName);
                
                $('.class-subjects-section').each(function() {
                    const classId = $(this).data('class-id');
                    window.WLSMSetupUtils.addQuickSubject(classId, subjectName);
                });
                
                window.WLSMSetupUtils.showInfoMessage(`Added "${subjectName}" to all classes`);
            });

            // Quick add single class
            $(document).off('click.subjects', '.quick-add-single')
                      .on('click.subjects', '.quick-add-single', function(e) {
                e.preventDefault();
                const classId = $(this).data('class-id');
                const subjectName = $(this).data('subject');
                console.log('Quick adding subject to class:', classId, subjectName);
                
                window.WLSMSetupUtils.addQuickSubject(classId, subjectName);
                window.WLSMSetupUtils.showInfoMessage(`Added "${subjectName}" to class`);
            });
        },

        initSubjectsNextButton: function() {
            // Override the next button for subjects step
            $(document).off('click.subjects', '.wlsm-setup-next-btn[data-step="subjects"]')
                      .on('click.subjects', '.wlsm-setup-next-btn[data-step="subjects"]', function(e) {
                console.log('Subjects next button clicked');
                e.preventDefault();
                
                // Validate subjects
                if (!window.WLSMSetupUtils.validateSubjects()) {
                    return false;
                }
                
                // Show loading
                if (typeof window.wlsmSetupWizard !== 'undefined') {
                    window.wlsmSetupWizard.showLoading();
                }
                
                // Process and save subjects
                window.WLSMSetupUtils.processAndSaveSubjects();
            });
        },

        addSubjectRow: function(classId) {
            const $container = $('.subjects-container[data-class-id="' + classId + '"]');
            const subjectTypes = this.getSubjectTypes();
            
            const newRow = `
                <div class="subject-item mb-2">
                    <div class="row">
                        <div class="col-sm-6">
                            <input type="text" 
                                   class="form-control form-control-sm subject-name" 
                                   placeholder="Subject name"
                                   data-class-id="${classId}">
                        </div>
                        <div class="col-sm-3">
                            <input type="text" 
                                   class="form-control form-control-sm subject-code" 
                                   placeholder="Code (optional)">
                        </div>
                        <div class="col-sm-2">
                            <select class="form-control form-control-sm subject-type">
                                ${subjectTypes}
                            </select>
                        </div>
                        <div class="col-sm-1">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-subject" title="Remove subject">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $container.append(newRow);
            this.updateSubjectCount(classId);
            
            // Focus on the new input
            $container.find('.subject-item').last().find('.subject-name').focus();
        },

        removeSubjectRow: function($subjectItem, classId) {
            $subjectItem.addClass('removing');
            setTimeout(function() {
                $subjectItem.remove();
                window.WLSMSetupUtils.updateSubjectCount(classId);
            }, 300);
        },

        clearAllSubjects: function(classId) {
            const $container = $('.subjects-container[data-class-id="' + classId + '"]');
            $container.find('.subject-item').each(function() {
                $(this).addClass('removing');
            });
            
            setTimeout(function() {
                $container.empty();
                // Add one empty row
                window.WLSMSetupUtils.addSubjectRow(classId);
            }, 300);
        },

        addQuickSubject: function(classId, subjectName) {
            const $container = $('.subjects-container[data-class-id="' + classId + '"]');
            
            // Check if subject already exists
            const exists = $container.find('.subject-name').filter(function() {
                return $(this).val().trim().toLowerCase() === subjectName.toLowerCase();
            }).length > 0;
            
            if (exists) {
                console.log('Subject already exists:', subjectName);
                return;
            }
            
            // Find first empty input or add new row
            let $targetInput = $container.find('.subject-name').filter(function() {
                return $(this).val().trim() === '';
            }).first();
            
            if ($targetInput.length === 0) {
                this.addSubjectRow(classId);
                $targetInput = $container.find('.subject-name').last();
            }
            
            // Set the subject name with animation
            $targetInput.val(subjectName).focus();
            
            // Trigger change event to update count
            setTimeout(function() {
                $targetInput.trigger('change');
            }, 100);
        },

        updateSubjectCount: function(classId) {
            const $container = $('.subjects-container[data-class-id="' + classId + '"]');
            const count = $container.find('.subject-name').filter(function() {
                return $(this).val().trim() !== '';
            }).length;
            
            const $countBadge = $(`.class-subjects-section[data-class-id="${classId}"] .subject-count`);
            $countBadge.text(count + (count === 1 ? ' subject' : ' subjects'));
            
            // Update badge color based on count
            $countBadge.removeClass('badge-light badge-success badge-warning')
                      .addClass(count === 0 ? 'badge-light' : count < 3 ? 'badge-warning' : 'badge-success');
        },

        updateAllSubjectCounts: function() {
            const self = this;
            $('.class-subjects-section').each(function() {
                const classId = $(this).data('class-id');
                self.updateSubjectCount(classId);
            });
        },

        getSubjectTypes: function() {
            // Get subject types from first existing select or use defaults
            const $firstSelect = $('.subject-type').first();
            if ($firstSelect.length > 0) {
                return $firstSelect.html();
            }
            
            // Default options if none found
            return `
                <option value="General" selected>General</option>
                <option value="Core">Core</option>
                <option value="Elective">Elective</option>
                <option value="Optional">Optional</option>
            `;
        },

        validateSubjects: function() {
            let isValid = true;
            const emptyClasses = [];
            
            $('.class-subjects-section').each(function() {
                const classId = $(this).data('class-id');
                const $container = $(this).find('.subjects-container');
                const hasSubjects = $container.find('.subject-name').filter(function() {
                    return $(this).val().trim() !== '';
                }).length > 0;
                
                if (!hasSubjects) {
                    isValid = false;
                    const className = $(this).find('.class-header h6').text().replace(/\d+\s+subjects?/, '').trim();
                    emptyClasses.push(className);
                }
            });
            
            if (!isValid) {
                const message = 'Please add at least one subject for: ' + emptyClasses.join(', ');
                this.showValidationError(message);
                return false;
            }
            
            this.hideValidationError();
            return true;
        },

        processAndSaveSubjects: function() {
            const subjects = this.collectSubjectsData();
            console.log('Collected subjects data:', subjects);
            
            if (subjects.length === 0) {
                if (typeof window.wlsmSetupWizard !== 'undefined') {
                    window.wlsmSetupWizard.hideLoading();
                    window.wlsmSetupWizard.showError('No subjects to save');
                }
                return;
            }
            
            let processedCount = 0;
            const totalSubjects = subjects.length;
            let hasErrors = false;
            
            console.log('Processing', totalSubjects, 'subjects');
            
            // Get the nonce from the form
            const nonceValue = $('input[name="add-subject"]').val();
            console.log('Using nonce:', nonceValue);
            
            // Process each subject
            subjects.forEach(function(subject, index) {
                const formData = new FormData();
                formData.append('action', 'wlsm-save-subject');
                formData.append('add-subject', nonceValue);
                formData.append('label', subject.name);
                formData.append('code', subject.code || '');
                formData.append('type', subject.type || 'General');
                formData.append('class_id[]', subject.classId);
                
                console.log('Saving subject:', subject.name, 'for class:', subject.classId, 'with nonce:', nonceValue);
                
                $.ajax({
                    url: wlsmSetupConfig.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Subject saved successfully:', subject.name, response);
                        processedCount++;
                        console.log('Processed count:', processedCount, 'of', totalSubjects);
                        
                        if (processedCount === totalSubjects) {
                            console.log('All subjects processed, calling handleAllSubjectsProcessed');
                            window.WLSMSetupUtils.handleAllSubjectsProcessed(hasErrors);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error saving subject:', subject.name, xhr.responseText, error);
                        hasErrors = true;
                        processedCount++;
                        console.log('Processed count with error:', processedCount, 'of', totalSubjects);
                        
                        if (processedCount === totalSubjects) {
                            console.log('All subjects processed (with errors), calling handleAllSubjectsProcessed');
                            window.WLSMSetupUtils.handleAllSubjectsProcessed(hasErrors);
                        }
                    }
                });
            });
        },

        collectSubjectsData: function() {
            const subjects = [];
            
            $('.class-subjects-section').each(function() {
                const classId = $(this).data('class-id');
                const $container = $(this).find('.subjects-container');
                
                $container.find('.subject-item').each(function() {
                    const name = $(this).find('.subject-name').val().trim();
                    const code = $(this).find('.subject-code').val().trim();
                    const type = $(this).find('.subject-type').val() || 'General';
                    
                    if (name) {
                        subjects.push({
                            name: name,
                            code: code,
                            type: type,
                            classId: classId
                        });
                    }
                });
            });
            
            return subjects;
        },

        handleAllSubjectsProcessed: function(hasErrors) {
            console.log('handleAllSubjectsProcessed called with hasErrors:', hasErrors);
            console.log('wlsmSetupWizard available:', typeof window.wlsmSetupWizard !== 'undefined');
            
            if (typeof window.wlsmSetupWizard !== 'undefined') {
                console.log('Hiding loading modal');
                window.wlsmSetupWizard.hideLoading();
                
                if (hasErrors) {
                    console.log('Showing error message');
                    window.wlsmSetupWizard.showError('Some subjects could not be saved. Please check the console for details.');
                } else {
                    console.log('Showing success message');
                    window.wlsmSetupWizard.showSuccess('All subjects saved successfully!');
                    
                    // Navigate to next step
                    const nextStep = $('.wlsm-setup-next-btn[data-step="subjects"]').data('next-step');
                    console.log('Next step from button:', nextStep);
                    console.log('wlsmSetupConfig.baseUrl:', wlsmSetupConfig.baseUrl);
                    
                    if (nextStep) {
                        const nextUrl = wlsmSetupConfig.baseUrl + '&step=' + nextStep;
                        console.log('Will redirect to:', nextUrl, 'in 1.5 seconds');
                        setTimeout(function() {
                            console.log('Redirecting now to:', nextUrl);
                            window.location.href = nextUrl;
                        }, 1500);
                    } else {
                        console.warn('No next step found in button data attributes');
                    }
                }
            } else {
                // Fallback if wlsmSetupWizard is not available
                console.log('wlsmSetupWizard not available, using fallback');
                if (hasErrors) {
                    alert('Some subjects could not be saved. Please check the console for details.');
                } else {
                    alert('All subjects saved successfully!');
                    
                    // Navigate to next step
                    const nextStep = $('.wlsm-setup-next-btn[data-step="subjects"]').data('next-step');
                    console.log('Fallback - Next step:', nextStep);
                    if (nextStep) {
                        window.location.href = wlsmSetupConfig.baseUrl + '&step=' + nextStep;
                    }
                }
            }
        },

        showValidationError: function(message) {
            const $alert = $('#subjects-validation-alert');
            $alert.find('#validation-message').text(message);
            $alert.show();
            
            $('html, body').animate({
                scrollTop: $alert.offset().top - 100
            }, 500);
        },

        hideValidationError: function() {
            $('#subjects-validation-alert').hide();
        },

        showInfoMessage: function(message) {
            const $alert = $('#subjects-info-alert');
            $alert.find('#info-message').text(message);
            $alert.show();
            
            setTimeout(function() {
                $alert.fadeOut();
            }, 3000);
        },

        // Student Types functionality
        initStudentTypesPage: function() {
            console.log('initStudentTypesPage called');

            // Setup form submission
            this.initStudentTypeForm();
            
            // Setup DataTable
            this.initStudentTypeDataTable();
            
            // Setup quick add functionality
            this.initQuickAddTypes();
            
            // Setup validation
            this.initStudentTypeValidation();
        },

        initStudentTypeForm: function() {
            const $form = $('#wlsm-save-student-type-form');
            
            if ($form.length === 0) {
                console.log('Student type form not found');
                return;
            }

            console.log('Setting up student type form');
            
            // Setup ajaxForm for student type submission
            $form.ajaxForm({
                dataType: 'json',
                beforeSubmit: function(arr, $form, options) {
                    console.log('Student type form beforeSubmit');
                    
                    // Clear previous messages
                    window.WLSMSetupUtils.hideStudentTypeMessages();
                    
                    // Show loading
                    const $submitBtn = $('#wlsm-save-student-type-btn');
                    $submitBtn.prop('disabled', true);
                    $submitBtn.find('i').removeClass().addClass('fas fa-spinner fa-spin');
                    
                    return true;
                },
                success: function(response) {
                    console.log('Student type form success:', response);
                    
                    const $submitBtn = $('#wlsm-save-student-type-btn');
                    $submitBtn.prop('disabled', false);
                    
                    if (response.success) {
                        // Show success message
                        window.WLSMSetupUtils.showStudentTypeSuccess(response.data.message || 'Student type saved successfully!');
                        
                        // Reset form if adding new (not editing)
                        if (response.data.reset) {
                            window.WLSMSetupUtils.resetStudentTypeForm();
                        }
                        
                        // Refresh DataTable
                        window.WLSMSetupUtils.refreshStudentTypeDataTable();
                        
                        // Reset button icon
                        const isEdit = $('input[name="student_type_id"]').length > 0;
                        $submitBtn.find('i').removeClass().addClass(isEdit ? 'fas fa-save' : 'fas fa-plus-circle');
                        
                    } else {
                        // Show error
                        let errorMessage = 'An error occurred while saving the student type.';
                        
                        if (typeof response.data === 'object') {
                            // Handle validation errors
                            const errors = [];
                            for (const field in response.data) {
                                errors.push(response.data[field]);
                            }
                            errorMessage = errors.join(', ');
                        } else if (response.data) {
                            errorMessage = response.data;
                        }
                        
                        window.WLSMSetupUtils.showStudentTypeError(errorMessage);
                        
                        // Reset button icon
                        const isEdit = $('input[name="student_type_id"]').length > 0;
                        $submitBtn.find('i').removeClass().addClass(isEdit ? 'fas fa-save' : 'fas fa-plus-circle');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Student type form error:', xhr.responseText, error);
                    
                    const $submitBtn = $('#wlsm-save-student-type-btn');
                    $submitBtn.prop('disabled', false);
                    
                    // Reset button icon
                    const isEdit = $('input[name="student_type_id"]').length > 0;
                    $submitBtn.find('i').removeClass().addClass(isEdit ? 'fas fa-save' : 'fas fa-plus-circle');
                    
                    window.WLSMSetupUtils.showStudentTypeError('Network error. Please try again.');
                }
            });
        },

        initStudentTypeDataTable: function() {
            const $table = $('#wlsm-class-student-type-table');
            
            if ($table.length === 0) {
                console.log('Student type table not found');
                return;
            }

            console.log('Initializing student type DataTable');
            
            // Check if DataTable is already initialized
            if ($.fn.DataTable.isDataTable($table)) {
                $table.DataTable().destroy();
            }
            
            // Initialize DataTable
            $table.DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wlsmSetupConfig ? wlsmSetupConfig.ajaxUrl : ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'wlsm-fetch-student-types';
                        return d;
                    }
                },
                columns: [
                    { data: 'DT_RowIndex', searchable: false, orderable: false },
                    { data: 'label' },
                    { data: 'actions', searchable: false, orderable: false, className: 'text-center' }
                ],
                responsive: true,
                language: {
                    emptyTable: 'No student types found',
                    processing: 'Loading student types...'
                },
                pageLength: 10,
                order: [[1, 'asc']]
            });
        },

        initQuickAddTypes: function() {
            // Quick add student type buttons
            $(document).off('click.studenttypes', '.quick-add-type')
                      .on('click.studenttypes', '.quick-add-type', function(e) {
                e.preventDefault();
                const typeName = $(this).data('type');
                console.log('Quick adding student type:', typeName);
                
                $('#wlsm_student_type_label').val(typeName).focus();
                
                // Show info message
                window.WLSMSetupUtils.showStudentTypeInfo(`"${typeName}" added to the form. Click "Add Student Type" to save.`);
            });
        },

        initStudentTypeValidation: function() {
            // Real-time validation for student type label
            $('#wlsm_student_type_label').on('input', function() {
                const value = $(this).val().trim();
                const maxLength = 191;
                
                if (value.length > maxLength) {
                    $(this).addClass('is-invalid');
                    window.WLSMSetupUtils.showStudentTypeError(`Student type name cannot exceed ${maxLength} characters.`);
                } else {
                    $(this).removeClass('is-invalid');
                    window.WLSMSetupUtils.hideStudentTypeMessages();
                }
            });
        },

        // Student Type utility functions
        showStudentTypeSuccess: function(message) {
            const $alert = $('#student-type-success-alert');
            $alert.find('#success-message').text(message);
            $alert.show();
            
            // Auto-hide after 5 seconds
            setTimeout(function() {
                $alert.fadeOut();
            }, 5000);
            
            // Scroll to top to show message
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        },

        showStudentTypeError: function(message) {
            const $alert = $('#student-type-error-alert');
            $alert.find('#error-message').text(message);
            $alert.show();
            
            // Scroll to top to show message
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        },

        showStudentTypeInfo: function(message) {
            // Use toastr if available, otherwise use alert
            if (typeof toastr !== 'undefined') {
                toastr.info(message);
            } else {
                alert(message);
            }
        },

        hideStudentTypeMessages: function() {
            $('#student-type-success-alert').hide();
            $('#student-type-error-alert').hide();
        },

        resetStudentTypeForm: function() {
            $('#wlsm_student_type_label').val('').removeClass('is-invalid');
            this.hideStudentTypeMessages();
        },

        refreshStudentTypeDataTable: function() {
            const $table = $('#wlsm-class-student-type-table');
            if ($table.length > 0 && $.fn.DataTable.isDataTable($table)) {
                $table.DataTable().ajax.reload();
            }
        },

        deleteStudentType: function(id, name) {
            if (!confirm(`Are you sure you want to delete the student type "${name}"? This action cannot be undone.`)) {
                return;
            }
            
            console.log('Deleting student type:', id, name);
            
            $.ajax({
                url: wlsmSetupConfig ? wlsmSetupConfig.ajaxUrl : ajaxurl,
                type: 'POST',
                data: {
                    action: 'wlsm-delete-student-type',
                    student_type_id: id,
                    _wpnonce: wlsmSetupConfig ? wlsmSetupConfig.nonce : ''
                },
                success: function(response) {
                    console.log('Delete student type response:', response);
                    
                    if (response.success) {
                        window.WLSMSetupUtils.showStudentTypeSuccess(response.data.message || 'Student type deleted successfully!');
                        window.WLSMSetupUtils.refreshStudentTypeDataTable();
                    } else {
                        window.WLSMSetupUtils.showStudentTypeError(response.data || 'Failed to delete student type.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete student type error:', error);
                    window.WLSMSetupUtils.showStudentTypeError('Network error. Please try again.');
                }
            });
        },

        // Classes step functionality (keeping existing)
        initClassesStep: function() {
            console.log('initClassesStep called');
            
            // Select all classes
            $('#select-all-classes').on('click', function() {
                console.log('Select all clicked');
                $('input[name="classes[]"]').prop('checked', true);
                $('#classes-validation-alert').hide();
            });
            
            // Clear all classes
            $('#clear-all-classes').on('click', function() {
                console.log('Clear all clicked');
                $('input[name="classes[]"]').prop('checked', false);
            });
            
            // Validation on checkbox change
            $('input[name="classes[]"]').on('change', function() {
                const checkedCount = $('input[name="classes[]"]:checked').length;
                if (checkedCount > 0) {
                    $('#classes-validation-alert').hide();
                }
            });
            
            // Setup the assign classes form using ajaxForm (same pattern as wlsm-admin.js)
            var assignClassesFormId = '#wlsm-step-classes-form';
            var assignClassesForm = $(assignClassesFormId);
            
            // Override the default next button behavior for classes step
            $(document).off('click.wlsm-setup-classes', '.wlsm-setup-next-btn[data-step="classes"]')
                      .on('click.wlsm-setup-classes', '.wlsm-setup-next-btn[data-step="classes"]', function(e) {
                console.log('Classes next button clicked');
                e.preventDefault();
                
                const checkedCount = $('input[name="classes[]"]:checked').length;
                console.log('Checked classes count:', checkedCount);
                
                if (checkedCount === 0) {
                    console.log('No classes selected - showing validation alert');
                    $('#classes-validation-alert').show();
                    $('html, body').animate({
                        scrollTop: $('#classes-validation-alert').offset().top - 100
                    }, 500);
                    return false;
                }
                
                console.log('Submitting classes form');
                // Submit the form using ajaxForm
                assignClassesForm.submit();
            });
            
            // Setup ajaxForm for assign classes (copied from wlsm-admin.js pattern)
            console.log('Setting up ajaxForm for:', assignClassesFormId);
            assignClassesForm.ajaxForm({
                dataType: 'json',
                beforeSubmit: function(arr, $form, options) {
                    console.log('ajaxForm beforeSubmit called');
                    console.log('Form data being sent:', arr);
                    console.log('Form action URL:', $form.attr('action'));
                    
                    // Show loading
                    if (typeof window.wlsmSetupWizard !== 'undefined') {
                        window.wlsmSetupWizard.showLoading();
                    }
                    return true;
                },
                success: function(response) {
                    console.log('ajaxForm success:', response);
                    console.log('Response type:', typeof response);
                    
                    if (typeof window.wlsmSetupWizard !== 'undefined') {
                        window.wlsmSetupWizard.hideLoading();
                    }
                    
                    // Check if response is a string (HTML) instead of object (JSON)
                    if (typeof response === 'string') {
                        console.error('Received HTML response instead of JSON:', response);
                        if (typeof window.wlsmSetupWizard !== 'undefined') {
                            window.wlsmSetupWizard.showError('Server returned invalid response. Please check console for details.');
                        }
                        return;
                    }
                    
                    if (response.success) {
                        if (typeof window.wlsmSetupWizard !== 'undefined') {
                            window.wlsmSetupWizard.showSuccess(response.data.message || 'Classes assigned successfully!');
                        }
                        
                        // Navigate to next step
                        const nextStep = $('.wlsm-setup-next-btn[data-step="classes"]').data('next-step');
                        if (nextStep) {
                            setTimeout(function() {
                                window.location.href = wlsmSetupConfig.baseUrl + '&step=' + nextStep;
                            }, 1500);
                        }
                    } else {
                        console.error('Server error:', response.data);
                        if (typeof window.wlsmSetupWizard !== 'undefined') {
                            window.wlsmSetupWizard.showError(response.data || 'An error occurred while assigning classes.');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log('ajaxForm error:', xhr, status, error);
                    console.log('Response text:', xhr.responseText);
                    
                    if (typeof window.wlsmSetupWizard !== 'undefined') {
                        window.wlsmSetupWizard.hideLoading();
                        window.wlsmSetupWizard.showError('Network error. Please try again. Check console for details.');
                    }
                },
                complete: function(event, xhr, settings) {
                    console.log('ajaxForm complete');
                    // Additional cleanup if needed
                }
            });
        },
        
        // Initialize student types step with improved functionality
        initStudentTypesStep: function() {
            console.log('initStudentTypesStep called');

            // Add/Remove Student Type handlers
            this.initStudentTypeManagement();
            
            // Quick add functionality
            this.initQuickAddStudentTypes();
            
            // Student type count updates
            this.updateStudentTypeCount();
            
            // Next button handler
            this.initStudentTypesNextButton();
        },

        initStudentTypeManagement: function() {
            // Add student type button
            $(document).off('click.student_types', '.add-student-type-btn')
                      .on('click.student_types', '.add-student-type-btn', function(e) {
                e.preventDefault();
                window.WLSMSetupUtils.addStudentTypeRow();
            });

            // Remove student type button
            $(document).off('click.student_types', '.remove-student-type')
                      .on('click.student_types', '.remove-student-type', function(e) {
                e.preventDefault();
                const $typeItem = $(this).closest('.student-type-item');
                window.WLSMSetupUtils.removeStudentTypeRow($typeItem);
            });

            // Clear all student types
            $(document).off('click.student_types', '.clear-all-types')
                      .on('click.student_types', '.clear-all-types', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to clear all student types?')) {
                    window.WLSMSetupUtils.clearAllStudentTypes();
                }
            });

            // Auto-update student type count when input values change
            $(document).off('keyup.student_types change.student_types', '.student-type-name')
                      .on('keyup.student_types change.student_types', '.student-type-name', function() {
                window.WLSMSetupUtils.updateStudentTypeCount();
                
                // Auto-add new row if this is the last row and has content
                const $container = $('.student-types-container');
                const $allInputs = $container.find('.student-type-name');
                const $lastInput = $allInputs.last();
                
                if ($(this).is($lastInput) && $(this).val().trim() !== '') {
                    window.WLSMSetupUtils.addStudentTypeRow();
                }
            });
        },

        initQuickAddStudentTypes: function() {
            // Quick add student type
            $(document).off('click.student_types', '.quick-add-student-type')
                      .on('click.student_types', '.quick-add-student-type', function(e) {
                e.preventDefault();
                const typeName = $(this).data('type');
                console.log('Quick adding student type:', typeName);
                
                window.WLSMSetupUtils.addQuickStudentType(typeName);
                window.WLSMSetupUtils.showStudentTypeInfoMessage(`Added "${typeName}" student type`);
            });
        },

        initStudentTypesNextButton: function() {
            // Override the next button for student types step
            $(document).off('click.student_types', '.wlsm-setup-next-btn[data-step="student_types"]')
                      .on('click.student_types', '.wlsm-setup-next-btn[data-step="student_types"]', function(e) {
                console.log('Student types next button clicked');
                e.preventDefault();
                
                // Validate student types
                if (!window.WLSMSetupUtils.validateStudentTypes()) {
                    return false;
                }
                
                // Show loading
                if (typeof window.wlsmSetupWizard !== 'undefined') {
                    window.wlsmSetupWizard.showLoading();
                }
                
                // Process and save student types
                window.WLSMSetupUtils.processAndSaveStudentTypes();
            });
        },

        addStudentTypeRow: function() {
            const $container = $('#student-types-container');
            
            const newRow = `
                <div class="student-type-item mb-2 p-2 border rounded">
                    <div class="row">
                        <div class="col-sm-10">
                            <input type="text" 
                                   name="student_types[]" 
                                   class="form-control form-control-sm student-type-name" 
                                   placeholder="Enter student type name">
                        </div>
                        <div class="col-sm-2">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-student-type" title="Remove type">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $container.append(newRow);
            this.updateStudentTypeCount();
            
            // Focus on the new input
            $container.find('.student-type-item').last().find('.student-type-name').focus();
        },

        removeStudentTypeRow: function($typeItem) {
            $typeItem.addClass('removing');
            setTimeout(function() {
                $typeItem.remove();
                window.WLSMSetupUtils.updateStudentTypeCount();
            }, 300);
        },

        clearAllStudentTypes: function() {
            const $container = $('#student-types-container');
            $container.find('.student-type-item').each(function() {
                $(this).addClass('removing');
            });
            
            setTimeout(function() {
                $container.empty();
                // Add one empty row
                window.WLSMSetupUtils.addStudentTypeRow();
            }, 300);
        },

        addQuickStudentType: function(typeName) {
            const $container = $('#student-types-container');
            
            // Check if student type already exists
            const exists = $container.find('.student-type-name').filter(function() {
                return $(this).val().trim().toLowerCase() === typeName.toLowerCase();
            }).length > 0;
            
            if (exists) {
                console.log('Student type already exists:', typeName);
                return;
            }
            
            // Find first empty input or add new row
            let $targetInput = $container.find('.student-type-name').filter(function() {
                return $(this).val().trim() === '';
            }).first();
            
            if ($targetInput.length === 0) {
                this.addStudentTypeRow();
                $targetInput = $container.find('.student-type-name').last();
            }
            
            // Set the student type name with animation
            $targetInput.val(typeName).focus();
            
            // Trigger change event to update count
            setTimeout(function() {
                $targetInput.trigger('change');
            }, 100);
        },

        updateStudentTypeCount: function() {
            const $container = $('#student-types-container');
            const count = $container.find('.student-type-name').filter(function() {
                return $(this).val().trim() !== '';
            }).length;
            
            const $countBadge = $('.student-type-count');
            $countBadge.text(count + (count === 1 ? ' type' : ' types'));
            
            // Update badge color based on count
            $countBadge.removeClass('badge-light badge-success badge-warning')
                      .addClass(count === 0 ? 'badge-light' : count < 3 ? 'badge-warning' : 'badge-success');
        },

        validateStudentTypes: function() {
            const $container = $('#student-types-container');
            const hasTypes = $container.find('.student-type-name').filter(function() {
                return $(this).val().trim() !== '';
            }).length > 0;
            
            if (!hasTypes) {
                const message = 'Please add at least one student type to continue.';
                this.showStudentTypeValidationError(message);
                return false;
            }
            
            this.hideStudentTypeValidationError();
            return true;
        },

        processAndSaveStudentTypes: function() {
            const studentTypes = this.collectStudentTypesData();
            console.log('Collected student types data:', studentTypes);
            
            if (studentTypes.length === 0) {
                if (typeof window.wlsmSetupWizard !== 'undefined') {
                    window.wlsmSetupWizard.hideLoading();
                    window.wlsmSetupWizard.showError('No student types to save');
                }
                return;
            }
            
            let processedCount = 0;
            const totalTypes = studentTypes.length;
            let hasErrors = false;
            
            console.log('Processing', totalTypes, 'student types');
            
            // Get the nonce from the form
            const nonceValue = $('input[name="add-student-type"]').val();
            console.log('Using nonce:', nonceValue);
            
            // Process each student type
            studentTypes.forEach(function(studentType, index) {
                const formData = new FormData();
                formData.append('action', 'wlsm-save-student-type');
                formData.append('add-student-type', nonceValue);
                formData.append('label', studentType.name);
                
                console.log('Saving student type:', studentType.name, 'with nonce:', nonceValue);
                
                $.ajax({
                    url: wlsmSetupConfig.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Student type saved successfully:', studentType.name, response);
                        processedCount++;
                        console.log('Processed count:', processedCount, 'of', totalTypes);
                        
                        if (processedCount === totalTypes) {
                            console.log('All student types processed, calling handleAllStudentTypesProcessed');
                            window.WLSMSetupUtils.handleAllStudentTypesProcessed(hasErrors);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error saving student type:', studentType.name, xhr.responseText, error);
                        hasErrors = true;
                        processedCount++;
                        console.log('Processed count with error:', processedCount, 'of', totalTypes);
                        
                        if (processedCount === totalTypes) {
                            console.log('All student types processed (with errors), calling handleAllStudentTypesProcessed');
                            window.WLSMSetupUtils.handleAllStudentTypesProcessed(hasErrors);
                        }
                    }
                });
            });
        },

        collectStudentTypesData: function() {
            const studentTypes = [];
            
            $('#student-types-container .student-type-item').each(function() {
                const name = $(this).find('.student-type-name').val().trim();
                
                if (name) {
                    studentTypes.push({
                        name: name
                    });
                }
            });
            
            return studentTypes;
        },

        handleAllStudentTypesProcessed: function(hasErrors) {
            console.log('handleAllStudentTypesProcessed called with hasErrors:', hasErrors);
            console.log('wlsmSetupWizard available:', typeof window.wlsmSetupWizard !== 'undefined');
            
            if (typeof window.wlsmSetupWizard !== 'undefined') {
                console.log('Hiding loading modal');
                window.wlsmSetupWizard.hideLoading();
                
                if (hasErrors) {
                    console.log('Showing error message');
                    window.wlsmSetupWizard.showError('Some student types could not be saved. Please check the console for details.');
                } else {
                    console.log('Showing success message');
                    window.wlsmSetupWizard.showSuccess('All student types saved successfully!');
                    
                    // Navigate to next step
                    const nextStep = $('.wlsm-setup-next-btn[data-step="student_types"]').data('next-step');
                    console.log('Next step from button:', nextStep);
                    console.log('wlsmSetupConfig.baseUrl:', wlsmSetupConfig.baseUrl);
                    
                    if (nextStep) {
                        const nextUrl = wlsmSetupConfig.baseUrl + '&step=' + nextStep;
                        console.log('Will redirect to:', nextUrl, 'in 1.5 seconds');
                        setTimeout(function() {
                            console.log('Redirecting now to:', nextUrl);
                            window.location.href = nextUrl;
                        }, 1500);
                    } else {
                        console.warn('No next step found in button data attributes');
                    }
                }
            } else {
                // Fallback if wlsmSetupWizard is not available
                console.log('wlsmSetupWizard not available, using fallback');
                if (hasErrors) {
                    alert('Some student types could not be saved. Please check the console for details.');
                } else {
                    alert('All student types saved successfully!');
                    
                    // Navigate to next step
                    const nextStep = $('.wlsm-setup-next-btn[data-step="student_types"]').data('next-step');
                    console.log('Fallback - Next step:', nextStep);
                    if (nextStep) {
                        window.location.href = wlsmSetupConfig.baseUrl + '&step=' + nextStep;
                    }
                }
            }
        },

        showStudentTypeValidationError: function(message) {
            const $alert = $('#student-types-validation-alert');
            $alert.find('#validation-message').text(message);
            $alert.show();
            
            $('html, body').animate({
                scrollTop: $alert.offset().top - 100
            }, 500);
        },

        hideStudentTypeValidationError: function() {
            $('#student-types-validation-alert').hide();
        },

        showStudentTypeInfoMessage: function(message) {
            const $alert = $('#student-types-info-alert');
            $alert.find('#info-message').text(message);
            $alert.show();
            
            setTimeout(function() {
                $alert.fadeOut();
            }, 3000);
        },

        // Fee Types functionality
        initFeeTypesStep: function() {
            console.log('initFeeTypesStep called');

            // Add/Remove Fee Type handlers
            this.initFeeTypeManagement();
            
            // Quick add functionality
            this.initQuickAddFeeTypes();
            
            // Initialize selectpickers
            this.initFeeTypeSelectPickers();
            
            // Fee type count updates and preview
            this.updateFeeTypeCount();
            this.updateFeePreview();
            
            // Next button handler
            this.initFeeTypesNextButton();
        },

        initFeeTypeSelectPickers: function() {
            // Initialize bootstrap-select for existing items
            $('.fee-types-container .selectpicker').selectpicker();
            
            // Update on change for validation and preview
            $(document).off('change.fee_select', '.fee-types-container select, .fee-types-container input')
                      .on('change.fee_select', '.fee-types-container select, .fee-types-container input', function() {
                window.WLSMSetupUtils.updateFeePreview();
                window.WLSMSetupUtils.updateFeeTypeCount();
            });
            
            // Also initialize when new selectpickers are added dynamically
            $(document).off('DOMNodeInserted.fee_select').on('DOMNodeInserted.fee_select', '.fee-types-container', function() {
                setTimeout(function() {
                    $('.fee-types-container .selectpicker:not(.bs-select-hidden)').selectpicker();
                }, 100);
            });
        },

        initFeeTypeManagement: function() {
            // Add fee type button
            $(document).off('click.fee_types', '.add-fee-type-btn')
                      .on('click.fee_types', '.add-fee-type-btn', function(e) {
                e.preventDefault();
                window.WLSMSetupUtils.addFeeTypeRow();
            });

            // Remove fee type button
            $(document).off('click.fee_types', '.remove-fee-type')
                      .on('click.fee_types', '.remove-fee-type', function(e) {
                e.preventDefault();
                const $typeItem = $(this).closest('.fee-type-item');
                window.WLSMSetupUtils.removeFeeTypeRow($typeItem);
            });

            // Clear all fee types
            $(document).off('click.fee_types', '.clear-all-fee-types')
                      .on('click.fee_types', '.clear-all-fee-types', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to clear all fee types?')) {
                    window.WLSMSetupUtils.clearAllFeeTypes();
                }
            });

            // Auto-update fee type count and preview when input values change
            $(document).off('keyup.fee_types change.fee_types', '.fee-type-name, .fee-type-amount')
                      .on('keyup.fee_types change.fee_types', '.fee-type-name, .fee-type-amount', function() {
                window.WLSMSetupUtils.updateFeeTypeCount();
                window.WLSMSetupUtils.updateFeePreview();
                
                // Auto-add new row if this is the last row and has content
                const $container = $('.fee-types-container');
                const $allInputs = $container.find('.fee-type-name');
                const $lastInput = $allInputs.last();
                
                if ($(this).hasClass('fee-type-name') && $(this).is($lastInput) && $(this).val().trim() !== '') {
                    window.WLSMSetupUtils.addFeeTypeRow();
                }
            });
        },

        initQuickAddFeeTypes: function() {
            // Quick add fee type
            $(document).off('click.fee_types', '.quick-add-fee-type')
                      .on('click.fee_types', '.quick-add-fee-type', function(e) {
                e.preventDefault();
                const label = $(this).data('label');
                const amount = $(this).data('amount');
                console.log('Quick adding fee type:', label, 'with amount:', amount);
                
                window.WLSMSetupUtils.addQuickFeeType(label, amount);
                window.WLSMSetupUtils.showFeeTypeInfoMessage(`Added "${label}" fee type`);
            });
        },

        initFeeTypesNextButton: function() {
            // Override the next button for fee types step
            $(document).off('click.fee_types', '.wlsm-setup-next-btn[data-step="fee_types"]')
                      .on('click.fee_types', '.wlsm-setup-next-btn[data-step="fee_types"]', function(e) {
                console.log('Fee types next button clicked');
                e.preventDefault();
                
                // Validate fee types
                if (!window.WLSMSetupUtils.validateFeeTypes()) {
                    return false;
                }
                
                // Show loading
                if (typeof window.wlsmSetupWizard !== 'undefined') {
                    window.wlsmSetupWizard.showLoading();
                }
                
                // Process and save fee types
                window.WLSMSetupUtils.processAndSaveFeeTypes();
            });
        },

        addFeeTypeRow: function() {
            const $container = $('.fee-types-container');
            const $template = $('#fee-type-template');
            
            if ($template.length === 0) {
                console.error('Fee type template not found');
                return;
            }
            
            // Clone the template
            const $newRow = $template.children().first().clone();
            
            // Generate unique IDs for checkboxes to avoid conflicts
            const uniqueId = 'fee-type-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            
            // Update checkbox IDs and labels
            $newRow.find('.fee-type-admission').attr('id', uniqueId + '-admission');
            $newRow.find('label[for=""]').first().attr('for', uniqueId + '-admission');
            
            $newRow.find('.fee-type-dashboard').attr('id', uniqueId + '-dashboard');
            $newRow.find('label[for=""]').last().attr('for', uniqueId + '-dashboard');
            
            // Add the new row to container
            $container.append($newRow);
            
            // Initialize selectpicker for new dropdowns
            $newRow.find('.selectpicker').selectpicker();
            
            console.log('Added new fee type row');
        },

        removeFeeTypeRow: function($typeItem) {
            $typeItem.addClass('removing');
            setTimeout(function() {
                $typeItem.remove();
                window.WLSMSetupUtils.updateFeeTypeCount();
                window.WLSMSetupUtils.updateFeePreview();
            }, 300);
        },

        clearAllFeeTypes: function() {
            const $container = $('.fee-types-container');
            $container.find('.fee-type-item').each(function() {
                $(this).addClass('removing');
            });
            
            setTimeout(function() {
                $container.empty();
                // Add one empty row
                window.WLSMSetupUtils.addFeeTypeRow();
            }, 300);
        },

        addQuickFeeType: function(label, amount) {
            const $container = $('.fee-types-container');
            
            // Check if fee type already exists
            const exists = $container.find('.fee-type-name').filter(function() {
                return $(this).val().trim().toLowerCase() === label.toLowerCase();
            }).length > 0;
            
            if (exists) {
                console.log('Fee type already exists:', label);
                return;
            }
            
            // Find first empty input or add new row
            let $targetInput = $container.find('.fee-type-name').filter(function() {
                return $(this).val().trim() === '';
            }).first();
            
            if ($targetInput.length === 0) {
                this.addFeeTypeRow();
                $targetInput = $container.find('.fee-type-name').last();
            }
            
            const $feeTypeItem = $targetInput.closest('.fee-type-item');
            
            // Set the fee type name and amount
            $targetInput.val(label);
            if (amount) {
                $feeTypeItem.find('.fee-type-amount').val(amount);
            }
            
            // Set default values for other fields
            // Select all classes by default
            const $classesSelect = $feeTypeItem.find('.fee-type-classes');
            if ($classesSelect.length > 0) {
                $classesSelect.find('option[value!=""]').prop('selected', true);
                $classesSelect.selectpicker('refresh');
            }
            
            // Select all student types by default
            const $studentTypesSelect = $feeTypeItem.find('.fee-type-student-types');
            if ($studentTypesSelect.length > 0) {
                $studentTypesSelect.find('option[value!=""]').prop('selected', true);
                $studentTypesSelect.selectpicker('refresh');
            }
            
            // Set period to "one-time" for all fee types
            const $periodSelect = $feeTypeItem.find('.fee-type-period');
            if ($periodSelect.length > 0) {
                console.log('Setting period to one-time, available options:', $periodSelect.find('option').map(function() { return this.value + ': ' + this.text; }).get());
                $periodSelect.val('one-time');
                console.log('Period select value after setting:', $periodSelect.val());
            }
            
            // Set "Auto Generate Invoice On Admission" checked for all fee types
            $feeTypeItem.find('.fee-type-admission').prop('checked', true);
            
            $targetInput.focus();
            
            // Trigger change event to update count and preview
            setTimeout(function() {
                $targetInput.trigger('change');
            }, 100);
        },

        updateFeeTypeCount: function() {
            const $container = $('.fee-types-container');
            const count = $container.find('.fee-type-name').filter(function() {
                return $(this).val().trim() !== '';
            }).length;
            
            const $countBadge = $('.fee-type-count');
            $countBadge.text(count + (count === 1 ? ' type' : ' types'));
            
            // Update badge color based on count
            $countBadge.removeClass('badge-light badge-success badge-warning')
                      .addClass(count === 0 ? 'badge-light' : count < 3 ? 'badge-warning' : 'badge-success');
        },

        updateFeePreview: function() {
            const $container = $('.fee-types-container');
            const $previewContent = $('.preview-content');
            const $totalBadge = $('.total-amount');
            
            let total = 0;
            let html = '';
            
            $container.find('.fee-type-item').each(function() {
                const name = $(this).find('.fee-type-name').val().trim();
                const amount = parseFloat($(this).find('.fee-type-amount').val()) || 0;
                const classes = $(this).find('.fee-type-classes').val() || [];
                const studentTypes = $(this).find('.fee-type-student-types').val() || [];
                const period = $(this).find('.fee-type-period').val() || '';
                
                if (name && amount > 0) {
                    let details = [];
                    if (classes.length > 0) {
                        details.push(`Classes: ${classes.length}`);
                    }
                    if (studentTypes.length > 0) {
                        details.push(`Student Types: ${studentTypes.length}`);
                    }
                    if (period) {
                        details.push(`Period: ${period}`);
                    }
                    
                    const detailsText = details.length > 0 ? details.join(', ') : 'Incomplete';
                    
                    html += `
                        <div class="mb-2 p-2 border-left border-primary">
                            <div class="d-flex justify-content-between">
                                <span class="font-weight-bold">${name}</span>
                                <span class="text-success font-weight-bold">₹${amount.toFixed(2)}</span>
                            </div>
                            <small class="text-muted">${detailsText}</small>
                        </div>
                    `;
                    total += amount;
                }
            });
            
            if (html === '') {
                html = `
                    <p class="text-muted text-center mb-0">
                        <i class="fas fa-info-circle"></i><br>
                        Fee types will appear here as you add them.
                    </p>
                `;
            }
            
            $previewContent.html(html);
            $totalBadge.text('₹' + total.toFixed(2));
        },

        validateFeeTypes: function() {
            const $container = $('.fee-types-container');
            let isValid = true;
            let errorMessage = '';
            
            // Check if there are any fee types
            const $feeTypeItems = $container.find('.fee-type-item');
            if ($feeTypeItems.length === 0) {
                errorMessage = 'Please add at least one fee type to continue.';
                this.showFeeTypeValidationError(errorMessage);
                return false;
            }
            
            // Validate each fee type
            let hasValidTypes = false;
            $feeTypeItems.each(function() {
                const name = $(this).find('.fee-type-name').val().trim();
                const amount = $(this).find('.fee-type-amount').val().trim();
                const period = $(this).find('.fee-type-period').val().trim();
                
                // Only validate if name is filled (indicates user started this item)
                if (name) {
                    hasValidTypes = true;
                    
                    if (!amount || parseFloat(amount) <= 0) {
                        errorMessage = 'Please enter a valid amount for all fee types.';
                        isValid = false;
                        return false;
                    }
                    
                    if (!period) {
                        errorMessage = 'Please select a period for all fee types.';
                        isValid = false;
                        return false;
                    }
                    
                    // Note: Classes and student types are now optional
                    // Users can configure them as needed for their specific requirements
                }
            });
            
            if (!hasValidTypes) {
                errorMessage = 'Please add at least one complete fee type to continue.';
                isValid = false;
            }
            
            if (!isValid) {
                this.showFeeTypeValidationError(errorMessage);
                return false;
            }
            
            this.hideFeeTypeValidationError();
            return true;
        },

        processAndSaveFeeTypes: function() {
            const feeTypes = this.collectFeeTypesData();
            console.log('Collected fee types data:', feeTypes);
            
            if (feeTypes.length === 0) {
                if (typeof window.wlsmSetupWizard !== 'undefined') {
                    window.wlsmSetupWizard.hideLoading();
                    window.wlsmSetupWizard.showError('No fee types to save');
                }
                return;
            }
            
            let processedCount = 0;
            const totalTypes = feeTypes.length;
            let hasErrors = false;
            
            console.log('Processing', totalTypes, 'fee types');
            
            // Get the nonce from the form
            const nonceValue = $('input[name="add-fee"]').val();
            console.log('Using nonce:', nonceValue);
            
            // Process each fee type
            feeTypes.forEach(function(feeType, index) {
                const formData = new FormData();
                formData.append('action', 'wlsm-save-fee');
                formData.append('add-fee', nonceValue);
                formData.append('label', feeType.name);
                formData.append('amount', feeType.amount);
                formData.append('period', feeType.period);
                
                // Add classes as array
                if (feeType.classes && feeType.classes.length > 0) {
                    feeType.classes.forEach(function(classId) {
                        formData.append('class_id[]', classId);
                    });
                }
                
                // Add student types as array
                if (feeType.studentTypes && feeType.studentTypes.length > 0) {
                    feeType.studentTypes.forEach(function(studentType) {
                        formData.append('student_type[]', studentType);
                    });
                }
                
                // Add checkboxes
                if (feeType.activeOnAdmission) {
                    formData.append('active_on_admission', '1');
                }
                if (feeType.dashboardDisable) {
                    formData.append('active_on_dashboard', '1');
                }
                
                console.log('Saving fee type:', feeType.name, 'with data:', feeType);
                
                $.ajax({
                    url: wlsmSetupConfig.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        console.log('Fee type saved successfully:', feeType.name, response);
                        processedCount++;
                        console.log('Processed count:', processedCount, 'of', totalTypes);
                        
                        if (processedCount === totalTypes) {
                            console.log('All fee types processed, calling handleAllFeeTypesProcessed');
                            window.WLSMSetupUtils.handleAllFeeTypesProcessed(hasErrors);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error saving fee type:', feeType.name, xhr.responseText, error);
                        hasErrors = true;
                        processedCount++;
                        console.log('Processed count with error:', processedCount, 'of', totalTypes);
                        
                        if (processedCount === totalTypes) {
                            console.log('All fee types processed (with errors), calling handleAllFeeTypesProcessed');
                            window.WLSMSetupUtils.handleAllFeeTypesProcessed(hasErrors);
                        }
                    }
                });
            });
        },

        collectFeeTypesData: function() {
            const feeTypes = [];
            
            $('.fee-types-container .fee-type-item').each(function() {
                const name = $(this).find('.fee-type-name').val().trim();
                const amount = parseFloat($(this).find('.fee-type-amount').val()) || 0;
                const classes = $(this).find('.fee-type-classes').val() || [];
                const studentTypes = $(this).find('.fee-type-student-types').val() || [];
                const period = $(this).find('.fee-type-period').val() || '';
                const activeOnAdmission = $(this).find('.fee-type-admission').is(':checked') ? 1 : 0;
                const dashboardDisable = $(this).find('.fee-type-dashboard').is(':checked') ? 1 : 0;
                
                // Only require name, amount, and period - classes and student types are optional
                if (name && amount > 0 && period) {
                    feeTypes.push({
                        name: name,
                        amount: amount,
                        classes: classes,
                        studentTypes: studentTypes,
                        period: period,
                        activeOnAdmission: activeOnAdmission,
                        dashboardDisable: dashboardDisable
                    });
                }
            });
            
            console.log('Collected fee types:', feeTypes);
            return feeTypes;
        },

        handleAllFeeTypesProcessed: function(hasErrors) {
            console.log('handleAllFeeTypesProcessed called with hasErrors:', hasErrors);
            console.log('wlsmSetupWizard available:', typeof window.wlsmSetupWizard !== 'undefined');
            
            if (typeof window.wlsmSetupWizard !== 'undefined') {
                console.log('Hiding loading modal');
                window.wlsmSetupWizard.hideLoading();
                
                if (hasErrors) {
                    console.log('Showing error message');
                    window.wlsmSetupWizard.showError('Some fee types could not be saved. Please check the console for details.');
                } else {
                    console.log('Showing success message');
                    window.wlsmSetupWizard.showSuccess('All fee types saved successfully!');
                    
                    // Navigate to next step
                    const nextStep = $('.wlsm-setup-next-btn[data-step="fee_types"]').data('next-step');
                    console.log('Next step from button:', nextStep);
                    console.log('wlsmSetupConfig.baseUrl:', wlsmSetupConfig.baseUrl);
                    
                    if (nextStep) {
                        const nextUrl = wlsmSetupConfig.baseUrl + '&step=' + nextStep;
                        console.log('Will redirect to:', nextUrl, 'in 1.5 seconds');
                        setTimeout(function() {
                            console.log('Redirecting now to:', nextUrl);
                            window.location.href = nextUrl;
                        }, 1500);
                    } else {
                        console.warn('No next step found in button data attributes');
                    }
                }
            } else {
                // Fallback if wlsmSetupWizard is not available
                console.log('wlsmSetupWizard not available, using fallback');
                if (hasErrors) {
                    alert('Some fee types could not be saved. Please check the console for details.');
                } else {
                    alert('All fee types saved successfully!');
                    
                    // Navigate to next step
                    const nextStep = $('.wlsm-setup-next-btn[data-step="fee_types"]').data('next-step');
                    console.log('Fallback - Next step:', nextStep);
                    if (nextStep) {
                        window.location.href = wlsmSetupConfig.baseUrl + '&step=' + nextStep;
                    }
                }
            }
        },

        showFeeTypeValidationError: function(message) {
            const $alert = $('#fee-types-validation-alert');
            $alert.find('#validation-message').text(message);
            $alert.show();
            
            $('html, body').animate({
                scrollTop: $alert.offset().top - 100
            }, 500);
        },

        hideFeeTypeValidationError: function() {
            $('#fee-types-validation-alert').hide();
        },

        showFeeTypeInfoMessage: function(message) {
            const $alert = $('#fee-types-info-alert');
            $alert.find('#info-message').text(message);
            $alert.show();
            
            setTimeout(function() {
                $alert.fadeOut();
            }, 3000);
        },

        // Initialize step-specific functionality when DOM is ready
        initStepFunctionality: function() {
            // Initialize classes step if we're on it
            if ($('.wlsm-classes-step').length > 0) {
                console.log('Classes step detected - initializing functionality');
                window.WLSMSetupUtils.initClassesStep();
            } else {
                console.log('Classes step not found');
            }
            
            // Initialize subjects step if we're on it
            if ($('.wlsm-subjects-step').length > 0) {
                console.log('Subjects step detected - initializing functionality');
                window.WLSMSetupUtils.initSubjectsStep();
            } else {
                console.log('Subjects step not found');
            }
                 // Initialize student types step if we're on it
        if ($('.wlsm-student-types-step').length > 0) {
            console.log('Student types step detected - initializing functionality');
            window.WLSMSetupUtils.initStudentTypesStep();
        } else {
            console.log('Student types step not found');
        }
        
        // Initialize fee types step if we're on it
        if ($('.wlsm-fee-types-step').length > 0) {
            console.log('Fee types step detected - initializing functionality');
            window.WLSMSetupUtils.initFeeTypesStep();
        } else {
            console.log('Fee types step not found');
        }
        
        // Initialize student types page if we're on it
        if ($('.wlsm-student-types-page').length > 0) {
            console.log('Student types page detected - initializing functionality');
            window.WLSMSetupUtils.initStudentTypesPage();
        } else {
            console.log('Student types page not found');
        }

        // Initialize registration settings step if we're on it
        if ($('.wlsm-registration-settings-step').length > 0) {
            console.log('Registration settings step detected - initializing functionality');
            window.WLSMSetupUtils.initRegistrationSettingsStep();
        } else {
            console.log('Registration settings step not found');
        }

   // Initialize welcome step if we're on it
   if ($('.wlsm-welcome-step').length > 0) {
            console.log('Welcome step detected - initializing functionality');
            window.WLSMSetupUtils.initWelcomeStep();
        } else {
            console.log('Welcome step not found');
        }
        }
    };

    // Initialize step-specific functionality when DOM is ready
    $(document).ready(function() {
        window.WLSMSetupUtils.initStepFunctionality();
    });

    // Registration Settings functionality
    window.WLSMSetupUtils.initRegistrationSettingsStep = function() {
        console.log('initRegistrationSettingsStep called');

        const $form = $('#wlsm-step-registration-settings-form');
        if ($form.length === 0) {
            console.log('Registration settings form not found');
            return;
        }

        // Override the next button for registration settings step
        $(document).off('click.registration_settings', '.wlsm-setup-next-btn[data-step="registration_settings"]')
                   .on('click.registration_settings', '.wlsm-setup-next-btn[data-step="registration_settings"]', function(e) {
            console.log('Registration settings next button clicked');
            e.preventDefault();
            $form.submit();
        });

        $form.ajaxForm({
            dataType: 'json',
            beforeSubmit: function() {
                console.log('Registration settings form beforeSubmit');
                if (typeof window.wlsmSetupWizard !== 'undefined') {
                    window.wlsmSetupWizard.showLoading();
                }
                return true;
            },
            success: function(response) {
                console.log('Registration settings form success:', response);
                if (typeof window.wlsmSetupWizard !== 'undefined') {
                    window.wlsmSetupWizard.hideLoading();
                }

                if (response.success) {
                    if (typeof window.wlsmSetupWizard !== 'undefined') {
                        window.wlsmSetupWizard.showSuccess(response.data.message || 'Registration settings saved successfully!');
                    }
                    
                    const nextStep = $('.wlsm-setup-next-btn[data-step="registration_settings"]').data('next-step');
                    if (nextStep) {
                        setTimeout(function() {
                            window.location.href = wlsmSetupConfig.baseUrl + '&step=' + nextStep;
                        }, 1500);
                    }
                } else {
                    if (typeof window.wlsmSetupWizard !== 'undefined') {
                        window.wlsmSetupWizard.showError(response.data || 'An error occurred while saving settings.');
                    }
                }
            },
            error: function(xhr) {
                console.error('Registration settings form error:', xhr.responseText);
                if (typeof window.wlsmSetupWizard !== 'undefined') {
                    window.wlsmSetupWizard.hideLoading();
                    window.wlsmSetupWizard.showError('Network error. Please try again.');
                }
            }
        });
    };

 // welcome step
 window.WLSMSetupUtils.initWelcomeStep = function() {
  console.log('initWelcomeStep called');

  // Override the next button for the welcome step
  $(document).off('click.wlsm-setup-welcome', '.wlsm-setup-next-btn[data-step="welcome"]')
    .on('click.wlsm-setup-welcome', '.wlsm-setup-next-btn[data-step="welcome"]', function(e) {
   console.log('Welcome next button clicked');
   e.preventDefault();

   // Navigate to the next step
   const nextStep = $(this).data('next-step');
   if (nextStep) {
    window.location.href = wlsmSetupConfig.baseUrl + '&step=' + nextStep;
   }
  });
 };

})(jQuery);
