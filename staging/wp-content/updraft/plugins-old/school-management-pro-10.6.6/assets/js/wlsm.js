 (function($) {
	'use strict';
	$(document).ready(function() {
		// Loading icon variables.
		var loaderContainer = $('<span/>', {
			'class': 'wlsm-loader wlsm-ml-2'
		});
		var loader = $('<img/>', {
			'src': wlsmadminurl + 'images/spinner.gif',
			'class': 'wlsm-loader-image wlsm-mb-1'
		});

		$(document).on('change', '#wlsm-select-all', function() {
			if($(this).is(':checked')) {
				$('.wlsm-select-single').prop('checked', true);
			} else {
				$('.wlsm-select-single').prop('checked', false);
			}
		});

		$('.SlectBox').SumoSelect();

		// Function: Before Submit.
		function wlsmBeforeSubmit(button) {
			$('div.wlsm-text-danger').remove();
			$(".wlsm-is-invalid").removeClass("wlsm-is-invalid");
			$('.wlsm-alert-dismissible').remove();
			button.prop('disabled', true);
			loaderContainer.insertAfter(button);
			loader.appendTo(loaderContainer);
			return true;
		}

		// Function: Display Form Erros.
		function wlsmDisplayFormErrors(response, formId) {
			if(response.data && $.isPlainObject(response.data)) {
				$(formId + ' :input').each(function() {
					var input = this;
					$(input).removeClass('wlsm-is-invalid');
					if(response.data[input.name]) {
						var errorSpan = '<div class="wlsm-text-danger wlsm-mt-1">' + response.data[input.name] + '</div>';
						$(input).addClass('wlsm-is-invalid');
						$(errorSpan).insertAfter(input);
					}
				});
			} else {
				var errorSpan = '<div class="wlsm-text-danger wlsm-mt-3">' + response.data + '<hr></div>';
				$(errorSpan).insertBefore(formId);
				toastr.error(response.data);
			}
		}

		// Function: Display Form Error.
		function wlsmDisplayFormError(response, formId, button) {
			button.prop('disabled', false);
			var errorSpan = '<div class="text-danger wlsm-mt-2"><span class="wlsm-font-bold">' + response.status + '</span>: ' + response.statusText + '<hr></div>';
			$(errorSpan).insertBefore(formId);
			toastr.error(response.data);
		}

		// Function: Complete.
		function wlsmComplete(button) {
			button.prop('disabled', false);
			loaderContainer.remove();
		}

		// Get students with pending invoices.
		var getPendingInvoicesStudentsSection = '#wlsm-get-pending-invoices-students-section';
		var getPendingInvoicesStudentsBtn = $('#wlsm-get-pending-invoices-students-btn');

		$(document).on('click', '#wlsm-get-pending-invoices-students-btn', function(e) {
			var studentsWithPendingInvoices = $('.wlsm-students-with-pending-invoices');

			var schoolId = $('#wlsm_school').val();
			var sessionId = $('#wlsm_session').val();
			var classId = $('#wlsm_school_class').val();
			var studentName = $('#wlsm_student_name').val();
			var studentAdmission = $('#wlsm_admission_number').val();
			var nonce = $(this).data('nonce');

			var data = {};
			data['school_id'] = schoolId;
			data['session_id'] = sessionId;
			data['class_id'] = classId;
			data['student_name'] = studentName;
			data['admission_number'] = studentAdmission;
			data['nonce'] = nonce;
			data['action'] = 'wlsm-p-get-students-with-pending-invoices';

			if(nonce) {
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					beforeSend: function() {
						return wlsmBeforeSubmit(getPendingInvoicesStudentsBtn);
					},
					success: function(response) {
						if(response.success) {
							studentsWithPendingInvoices.html(response.data.html);
						} else {
							wlsmDisplayFormErrors(response, getPendingInvoicesStudentsSection);
						}
					},
					error: function(response) {
						wlsmDisplayFormError(response, getPendingInvoicesStudentsSection, getPendingInvoicesStudentsBtn);
					},
					complete: function(event, xhr, settings) {
						wlsmComplete(getPendingInvoicesStudentsBtn);
					},
				});
			} else {
				studentsWithPendingInvoices.html('');
			}
		});

		$(document).on('click', '#wlsm-get-pending-invoices-history-btn', function(e) {
			var studentsWithPendingInvoices = $('.wlsm-students-with-pending-invoices');

			var schoolId      = $('#wlsm_school').val();
			var sessionIdFrom = $('#wlsm_session_from').val();
			var sessionIdTo   = $('#wlsm_session_to').val();
			var nonce         = $(this).data('nonce');

			var  data               = {};
			data['school_id']       = schoolId;
			data['session_id_from'] = sessionIdFrom;
			data['session_id_to']   = sessionIdTo;
			data['nonce']           = nonce;
			data['action']          = 'wlsm-p-get-pending-invoices-history';

			if(nonce) {
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					beforeSend: function() {
						return wlsmBeforeSubmit(getPendingInvoicesStudentsBtn);
					},
					success: function(response) {
						if(response.success) {
							studentsWithPendingInvoices.html(response.data.html);
						} else {
							wlsmDisplayFormErrors(response, getPendingInvoicesStudentsSection);
						}
					},
					error: function(response) {
						wlsmDisplayFormError(response, getPendingInvoicesStudentsSection, getPendingInvoicesStudentsBtn);
					},
					complete: function(event, xhr, settings) {
						wlsmComplete(getPendingInvoicesStudentsBtn);
					},
				});
			} else {
				studentsWithPendingInvoices.html('');
			}
		});

		// Get student pending fee invoices.
		$(document).on('click', '.wlsm-view-student-pending-invoices', function(e) {
			e.preventDefault();
			var viewStudentInvoicesBtn = $(this);

			var studentPendingInvoices = $('.wlsm-student-pending-invoices');

			var studentId = $(this).data('student');
			var nonce = $(this).data('nonce');

			var data = {};
			data['student_id'] = studentId;
			data['nonce'] = nonce;
			data['action'] = 'wlsm-p-get-student-pending-invoices';

			if(nonce) {
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					beforeSend: function() {
						return wlsmBeforeSubmit(viewStudentInvoicesBtn);
					},
					success: function(response) {
						if(response.success) {
							studentPendingInvoices.html(response.data.html);
							studentPendingInvoices.focus();
							$(window).scrollTop(studentPendingInvoices.offset().top - ($(window).height() - studentPendingInvoices.outerHeight(true)) / 2);
						}
					},
					complete: function(event, xhr, settings) {
						wlsmComplete(viewStudentInvoicesBtn);
					},
				});
			} else {
				studentPendingInvoices.html('');
			}
		});

		// Get student pending fee invoice.
		$(document).on('click', '.wlsm-view-student-pending-invoice', function(e) {
			e.preventDefault();
			var viewStudentInvoiceBtn = $(this);

			var studentPendingInvoice = $('.wlsm-student-pending-invoice');

			var invoiceId = $(this).data('invoice');
			var nonce = $(this).data('nonce');

			var data = {};
			data['invoice_id'] = invoiceId;
			data['nonce'] = nonce;
			data['action'] = 'wlsm-p-get-student-pending-invoice';

			if(nonce) {
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					beforeSend: function() {
						return wlsmBeforeSubmit(viewStudentInvoiceBtn);
					},
					success: function(response) {
						if(response.success) {
							studentPendingInvoice.html(response.data.html);
							$(window).scrollTop(studentPendingInvoice.offset().top - ($(window).height() - studentPendingInvoice.outerHeight(true)) / 2);
						}
					},
					complete: function(event, xhr, settings) {
						wlsmComplete(viewStudentInvoiceBtn);
					},
				});
			} else {
				studentPendingInvoice.html('');
			}
		});

			// Get student pending fee invoice bulk.
			$(document).on('click', '.wlsm-view-student-pending-invoice-bulk', function(e) {
				e.preventDefault();
				var viewStudentInvoiceBtn = $(this);

				var studentPendingInvoice = $('.wlsm-student-pending-invoice');

				// var invoiceId = $(this).data('invoice');
				var invoiceIdBulkValues = $("input[name='invoice_ids[]']:checked")
				.map(function() {
					return $(this).val();
				}).get();

				var nonce = $(this).data('nonce');

				var data = {};
				data['invoice_ids'] = invoiceIdBulkValues;
				data['nonce'] = nonce;
				data['action'] = 'wlsm-p-get-student-pending-invoice-bulk';

				if(nonce) {
					$.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						beforeSend: function() {
							return wlsmBeforeSubmit(viewStudentInvoiceBtn);
						},
						success: function(response) {
							if(response.success) {
								studentPendingInvoice.html(response.data.html);
								$(window).scrollTop(studentPendingInvoice.offset().top - ($(window).height() - studentPendingInvoice.outerHeight(true)) / 2);
							}
						},
						complete: function(event, xhr, settings) {
							wlsmComplete(viewStudentInvoiceBtn);
						},
					});
				} else {
					studentPendingInvoice.html('');
				}
			});



		$(document).on('click', '#wlsm-pay-invoice-amount-btn', function(e) {
			var payInvoiceAmountSectionId = '#wlsm-pay-invoice-amount-section';
			var payInvoiceAmountBtn = $(this);

			var payInvoiceAmount = $('.wlsm-pay-invoice-amount');

			var invoiceId = $('#wlsm_invoice_id').val();
			var paymentAmount = $('#wlsm_payment_amount').val();
			var paymentMethod = $('input[name="payment_method"]:checked').val();
			var nonce = $(this).data('nonce');

			var invoiceIds = $("input[name='invoice_ids[]']:checked")
				.map(function() {
					return $(this).val();
				}).get();

			var data = {};
			data['invoice_id'] = invoiceId;
			data['invoice_ids'] = invoiceIds;
			data['payment_amount'] = paymentAmount;
			data['payment_method'] = paymentMethod;
			data['current_page_url'] = window.location.href;
			data['nonce'] = nonce;
			data['action'] = 'wlsm-p-pay-invoice-amount';

			var formData = new FormData();
			formData.append('invoice_id', data['invoice_id']);
			formData.append('invoice_ids', data['invoice_ids']);
			formData.append('payment_amount', data['payment_amount']);
			formData.append('payment_method', data['payment_method']);
			formData.append('current_page_url', data['current_page_url']);
			formData.append('nonce', data['nonce']);
			formData.append('action', data['action']);

			if('bank-transfer' === paymentMethod) {
				var bankTransferTransactionId = $('#wlsm_bank_transfer_transaction_id');
				if(bankTransferTransactionId) {
					data['bank_transfer_transaction_id'] = bankTransferTransactionId.val();
					formData.append('bank_transfer_transaction_id', data['bank_transfer_transaction_id']);
				}
				formData.append('bank_transfer_receipt', $('#wlsm_bank_transfer_receipt')[0].files[0]);
			}

			if('upi-transfer' === paymentMethod) {
				var upiTransferTransactionId = $('#wlsm_upi_transfer_transaction_id');
				if(upiTransferTransactionId) {
					data['upi_transfer_transaction_id'] = upiTransferTransactionId.val();
					formData.append('upi_transfer_transaction_id', data['upi_transfer_transaction_id']);
				}
				formData.append('upi_transfer_receipt', $('#wlsm_upi_transfer_receipt')[0].files[0]);
			}

			if(nonce) {
				$.ajax({
					data: formData,
					url: wlsmajaxurl,
					type: 'POST',
					beforeSend: function() {
						return wlsmBeforeSubmit(payInvoiceAmountBtn);
					},
					success: function(response) {
						if(response.success) {
							var data = response.data.json ? JSON.parse(response.data.json) : false;
							var html = response.data.html;
							payInvoiceAmount.html(html);

							if(!data) {
								return;
							}

							if ('razorpay' === data.payment_method) {
								// Razorpay Options.
								var options = {
									'key': data.razorpay_key,
									'amount': data.amount_in_paisa,
									'currency': data.currency,
									'name': data.school_name,
									'description': data.description,
									'image': data.school_logo_url,
									'handler': function(response) {
										var razorpayData = {
											'action': data.action,
											'security': data.security,
											'invoice_id': data.invoice_id,
											'invoice_ids': data.invoice_ids,
											'payment_id': response.razorpay_payment_id,
											'amount': parseFloat(data.amount_in_paisa)
										};

										// Send Razorpay data to server.
										$.ajax({
											type: 'POST',
											url: wlsmajaxurl,
											data: razorpayData,
											success: function (response) {
												if (response.success) {
													toastr.success(response.data.message);
													location.reload();
												} else {
													toastr.error(response.data);
												}
											},
											error: function (response) {
												toastr.error(response.statusText);
											},
											dataType: 'json'
										});
									},
									'prefill': {
										'name': data.name,
										'email': data.email
									},
									'notes': {
										'invoice_id': data.invoice_id,
										'invoice_number': data.invoice_number,
									},
									'theme': {
										'color': '#F37254'
									}
								};

								// Initialize Razorpay.
								var rzp = new Razorpay(options);

								// Open Razorpay payment window.
								$(document).on('click', '#wlsm-razorpay-btn', function(e) {
									rzp.open();
									e.preventDefault();
								});

							} else if ('stripe' === data.payment_method) {
								// Stripe Options.
								var options = {
									'key': data.stripe_key,
									'image': data.school_logo_url,
									'token': function(token) {
										var stripeData = {
											'action': data.action,
											'security': data.security,
											'invoice_id': data.invoice_id,
											'invoice_number': data.invoice_number,
											'amount': data.amount_in_cents,
											'stripeToken': token.id,
											'stripeEmail': token.email
										}

										// Send Stripe data to server.
										$.ajax({
											type: 'POST',
											url: wlsmajaxurl,
											data: stripeData,
											success: function (response) {
												if (response.success) {
													toastr.success(response.data.message);
													location.reload();
												} else {
													toastr.error(response.data);
												}
											},
											error: function (response) {
												toastr.error(response.statusText);
											},
											dataType: 'json'
										});
									}
								};

								// Initialize Stripe.
						 		var stripe = StripeCheckout.configure(options);

						 		// Open Stripe payment window.
								$(document).on('click', '#wlsm-stripe-btn', function(e) {
									stripe.open({
										name: data.name,
										description: data.description,
										currency: data.currency,
										amount: parseFloat(data.amount_in_cents)
									});
									e.preventDefault();
								});

								// Close stripe checkout on page navigation.
								$(window).on('popstate', function () {
									stripe.close();
								});

							} else if ('paypal' === data.payment_method) {
								$('input[name="cancel_return"]').val(window.location.href);
								$('input[name="return"]').val(window.location.href);
							} else if ('amberpay' === data.payment_method) {
								$('input[name="cancel_return"]').val(window.location.href);
								$('input[name="return"]').val(window.location.href);
							} else if ( 'pesapal' === data.payment_method) {
							} else if ('paystack' === data.payment_method) {
								var ptk = PaystackPop.setup({
									key: data.paystack_public_key,
									email: data.email,
									amount: data.amount_x_100,
									currency: data.currency,
									metadata: {
										custom_fields: [
											{
												display_name: data.school_name,
												phone: data.phone,
												invoice_id: data.invoice_id,
												amount: parseFloat(data.amount_x_100)
											}
										]
									},
									callback: function(response) {
										var paystackData = {
											'action': data.action,
											'security': data.security,
											'invoice_id': data.invoice_id,
											'amount': parseFloat(data.amount_x_100),
											'reference': response.reference
										};

										// Send Paystack data to server.
										$.ajax({
											type: 'POST',
											url: wlsmajaxurl,
											data: paystackData,
											success: function (response) {
												if (response.success) {
													toastr.success(response.data.message);
													location.reload();
												} else {
													toastr.error(response.data);
												}
											},
											error: function (response) {
												toastr.error(response.statusText);
											},
											dataType: 'json'
										});
									},
									onClose: function() {
									}
								});

								// Open Paystack payment window.
								$(document).on('click', '#wlsm-paystack-btn', function(e) {
								    ptk.openIframe();
									e.preventDefault();
								});
							} else if ('sslcommerz' === data.payment_method) {
								window.location.replace(data.return_data.redirect_url);
							} else if ('payu' === data.payment_method) {
								$('#' + data.form_id).submit();
							} else if ('paytm' === data.payment_method) {
								$('#' + data.form_id).submit();
							} else if ('bank-transfer' === data.payment_method) {
								$('input[name="bank_transfer_transaction_id"]').val('');
								$('input[name="bank_transfer_receipt"]').val('');
								toastr.success(data.message);
							}else if ('upi-transfer' === data.payment_method) {
								$('input[name="upi_transfer_transaction_id"]').val('');
								$('input[name="upi_transfer_receipt"]').val('');
								toastr.success(data.message);
							}
						} else {
							wlsmDisplayFormErrors(response, payInvoiceAmountSectionId);
						}
					},
					error: function(response) {
						wlsmDisplayFormError(response, payInvoiceAmountSectionId, payInvoiceAmountBtn);
					},
					complete: function(event, xhr, settings) {
						wlsmComplete(payInvoiceAmountBtn);
					},
					 contentType: false,
					 processData: false
				});
			} else {
				payInvoiceAmount.html('');
			}
		});

		// On change payment method.
		$(document).on('change', '#wlsm-pay-invoice-amount-section input[name="payment_method"]', function(e) {
			var paymentMethod = this.value;
			var bankTransferDetail = $('.wlsm-bank-transfer-detail');
			var upiTransferDetail = $('.wlsm-upi-transfer-detail');
			if('bank-transfer' === paymentMethod) {
				bankTransferDetail.show();
				upiTransferDetail.hide();
			} else if('upi-transfer' === paymentMethod) {
				upiTransferDetail.show();
				bankTransferDetail.hide();
			} else {
				bankTransferDetail.hide();
				upiTransferDetail.hide();
			}
		});

		// Save account settings.
		var saveAccountSettingsFormId = '#wlsm-save-settings-form';
		var saveAccountSettingsForm = $(saveAccountSettingsFormId);
		var saveAccountSettingsBtn = $('#wlsm-save-settings-btn');
		saveAccountSettingsForm.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				return wlsmBeforeSubmit(saveAccountSettingsBtn);
			},
			success: function(response) {
				if(response.success) {
					toastr.success(response.data.message);
					window.location.reload();
				} else {
					wlsmDisplayFormErrors(response, saveAccountSettingsFormId);
					if(!(response.data && $.isPlainObject(response.data))) {
						window.location.reload();
					}
				}
			},
			error: function(response) {
				wlsmDisplayFormError(response, saveAccountSettingsFormId, saveAccountSettingsBtn);
				window.location.reload();
			},
			complete: function(event, xhr, settings) {
				wlsmComplete(saveAccountSettingsBtn);
			}
		});

		// Submit inquiry.
		var submitInquiryFormId = '#wlsm-submit-inquiry-form';
		var submitInquiryForm = $(submitInquiryFormId);
		var submitInquiryBtn = $('#wlsm-submit-inquiry-btn');
		submitInquiryForm.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				return wlsmBeforeSubmit(submitInquiryBtn);
			},
			success: function(response) {
				if(response.success) {
					toastr.success(response.data.message);
						submitInquiryForm.html('<div class="wlsm-alert wlsm-alert-success" role="alert">' + response.data.message + '</div>');
						if(response.data.hasOwnProperty('redirect_url') && response.data.redirect_url && ('#' !== response.data.redirect_url)) {
							setTimeout(function () {
								window.location.href = response.data.redirect_url;
							}, 1300);
						 }
				} else {
					wlsmDisplayFormErrors(response, submitInquiryFormId);
				}
			},
			error: function(response) {
				wlsmDisplayFormError(response, submitInquiryFormId, submitInquiryBtn);
			},
			complete: function(event, xhr, settings) {
				wlsmComplete(submitInquiryBtn);
			}
		});

		  // Date of birth.
		$('#wlsm_date_of_birth').Zebra_DatePicker({
			format: wlsmdateformat,
			readonly_element: false,
			show_clear_date: true,
			disable_time_picker: true,
			view: 'years',
			direction: false
		});

		// Joining date.
		$('#wlsm_joining_date').Zebra_DatePicker({
			format: wlsmdateformat,
			readonly_element: false,
			show_clear_date: true,
			disable_time_picker: true
		});

		// Allow student login.
		$(document).on('change', '#wlsm_allow_student_login', function() {
			var studentNewUser = $('.wlsm-student-new-user');
			var parentLoginCheckbox = $('#wlsm_allow_parent_login');
			var parentLoginLabel = $('label[for="wlsm_allow_parent_login"]');
			var parentNewUser = $('.wlsm-parent-new-user');

			if($(this).is(':checked')) {
				studentNewUser.fadeIn();
				// Enable parent login option
				if (parentLoginCheckbox.length) {
					parentLoginCheckbox.prop('disabled', false);
					parentLoginLabel.css('opacity', '1');
				}
			} else {
				studentNewUser.hide();
				// Disable parent login option
				if (parentLoginCheckbox.length) {
					parentLoginCheckbox.prop('disabled', true).prop('checked', false);
					parentLoginLabel.css('opacity', '0.6');
					parentNewUser.hide();
				}
			}
		});

		// Allow parent login.
		$(document).on('change', '#wlsm_allow_parent_login', function() {
			var parentNewUser = $('.wlsm-parent-new-user')
			if($(this).is(':checked')) {
				parentNewUser.fadeIn();
			} else {
				parentNewUser.hide();
			}
		});

		// Initialize parent login dependency on page load
		$(document).ready(function() {
			var studentLoginCheckbox = $('#wlsm_allow_student_login');
			var parentLoginCheckbox = $('#wlsm_allow_parent_login');
			var parentLoginLabel = $('label[for="wlsm_allow_parent_login"]');

			if (studentLoginCheckbox.length && parentLoginCheckbox.length) {
				if (!studentLoginCheckbox.is(':checked')) {
					parentLoginCheckbox.prop('disabled', true);
					parentLoginLabel.css('opacity', '0.6');
				}
			}
		});

		// Submit schoolRegistration.
		var submitSchoolRegistrationFormId = '#wlsm-school-register-form';
		var submitSchoolRegistrationForm = $(submitSchoolRegistrationFormId);
		var submitSchoolRegistrationBtn = $('#wlsm-school-register-btn');
		submitSchoolRegistrationForm.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				return wlsmBeforeSubmit(submitSchoolRegistrationBtn);
			},
			success: function(response) {
				if(response.success) {
					toastr.success(response.data.message);
					submitSchoolRegistrationForm.html('<div class="wlsm-alert wlsm-alert-success" role="alert">' + response.data.message + '</div>');
				} else {
					wlsmDisplayFormErrors(response, submitSchoolRegistrationFormId);
				}
			},
			error: function(response) {
				wlsmDisplayFormError(response, submitSchoolRegistrationFormId, submitSchoolRegistrationBtn);
			},
			complete: function(event, xhr, settings) {
				wlsmComplete(submitSchoolRegistrationBtn);
			}
		});

		// Submit registration.
		var submitRegistrationFormId = '#wlsm-submit-registration-form';
		var submitRegistrationForm = $(submitRegistrationFormId);
		var submitRegistrationBtn = $('#wlsm-submit-registration-btn');
		submitRegistrationForm.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				return wlsmBeforeSubmit(submitRegistrationBtn);
			},
			success: function(response) {
				if(response.success) {
					toastr.success(response.data.message);
					submitRegistrationForm.html('<div class="wlsm-alert wlsm-alert-success" role="alert">' + response.data.message + '</div>');
					if(response.data.hasOwnProperty('redirect_url') && response.data.redirect_url && ('#' !== response.data.redirect_url)) {
						setTimeout(function () {
							window.location.href = response.data.redirect_url;
						}, 1300);
					}
				} else {
					wlsmDisplayFormErrors(response, submitRegistrationFormId);
				}
			},
			error: function(response) {
				wlsmDisplayFormError(response, submitRegistrationFormId, submitRegistrationBtn);
			},
			complete: function(event, xhr, settings) {
				wlsmComplete(submitRegistrationBtn);
			}
		});

		// Submit staff registration.
		var submitStaffRegistrationFormId = '#wlsm-submit-staff-registration-form';
		var submitStaffRegistrationForm = $(submitStaffRegistrationFormId);
		var submitStaffRegistrationBtn = $('#wlsm-submit-staff-registration-btn');
		submitStaffRegistrationForm.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				return wlsmBeforeSubmit(submitStaffRegistrationBtn);
			},
			success: function(response) {
				if(response.success) {
					toastr.success(response.data.message);
					submitStaffRegistrationForm.html('<div class="wlsm-alert wlsm-alert-success" role="alert">' + response.data.message + '</div>');
					if(response.data.hasOwnProperty('redirect_url') && response.data.redirect_url && ('#' !== response.data.redirect_url)) {
						setTimeout(function () {
							window.location.href = response.data.redirect_url;
						}, 1300);
					}
				} else {
					wlsmDisplayFormErrors(response, submitStaffRegistrationFormId);
				}
			},
			error: function(response) {
				wlsmDisplayFormError(response, submitStaffRegistrationFormId, submitStaffRegistrationBtn);
			},
			complete: function(event, xhr, settings) {
				wlsmComplete(submitStaffRegistrationBtn);
			}
		});

		// Get exam time table.
		var getExamTimeTableFormId = '#wlsm-get-exam-time-table-form';
		var getExamTimeTableForm = $(getExamTimeTableFormId);
		var getExamTimeTableBtn = $('#wlsm-get-exam-time-table-btn');
		var examTimeTable = $('.wlsm-exam-time-table');
		getExamTimeTableForm.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				return wlsmBeforeSubmit(getExamTimeTableBtn);
			},
			success: function(response) {
				if(response.success) {
					examTimeTable.html(response.data.html);
					$(window).scrollTop(examTimeTable.offset().top - ($(window).height() - examTimeTable.outerHeight(true)) / 2);
				} else {
					wlsmDisplayFormErrors(response, getExamTimeTableFormId);
					examTimeTable.html('');
				}
			},
			error: function(response) {
				wlsmDisplayFormError(response, getExamTimeTableFormId, getExamTimeTableBtn);
				examTimeTable.html('');
			},
			complete: function(event, xhr, settings) {
				wlsmComplete(getExamTimeTableBtn);
			}
		});

		// Get exam admit card.
		var getExamAdmitCardFormId = '#wlsm-get-exam-admit-card-form';
		var getExamAdmitCardForm = $(getExamAdmitCardFormId);
		var getExamAdmitCardBtn = $('#wlsm-get-exam-admit-card-btn');
		var examAdmitCard = $('.wlsm-exam-admit-card');
		getExamAdmitCardForm.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				return wlsmBeforeSubmit(getExamAdmitCardBtn);
			},
			success: function(response) {
				if(response.success) {
					examAdmitCard.html(response.data.html);
					$(window).scrollTop(examAdmitCard.offset().top - ($(window).height() - examAdmitCard.outerHeight(true)) / 2);
				} else {
					wlsmDisplayFormErrors(response, getExamAdmitCardFormId);
					examAdmitCard.html('');
				}
			},
			error: function(response) {
				wlsmDisplayFormError(response, getExamAdmitCardFormId, getExamAdmitCardBtn);
				examAdmitCard.html('');
			},
			complete: function(event, xhr, settings) {
				wlsmComplete(getExamAdmitCardBtn);
			}
		});

		// Get student lesson.
var getStudentLessonFormId = '#wlsm-get-student-lesson-form';
var getStudentLessonForm = $(getStudentLessonFormId);
var getStudentLessonBtn = $('#wlsm-get-student-lesson-btn');

getStudentLessonForm.ajaxForm({
    beforeSubmit: function(arr, $form, options) {
        return wlsmBeforeSubmit(getStudentLessonBtn);
    },
    success: function(response) {
        if(response.success) {
            // Replace the existing lessons container with the new filtered results
            $('.wlsm-grid').html(response.data.html);

            // Scroll to the top of the results
            $('html, body').animate({
                scrollTop: $('.wlsm-grid').offset().top - 50
            }, 500);

            // Re-initialize the form to maintain filter functionality
            getStudentLessonForm = $(getStudentLessonFormId);
            getStudentLessonBtn = $('#wlsm-get-student-lesson-btn');
        } else {
            wlsmDisplayFormErrors(response, getStudentLessonFormId);
        }
    },
    error: function(response) {
        wlsmDisplayFormError(response, getStudentLessonFormId, getStudentLessonBtn);
    },
    complete: function(event, xhr, settings) {
        wlsmComplete(getStudentLessonBtn);
    }
});


		// Get exam result.
		var getExamResultFormId = '#wlsm-get-exam-result-form';
		var getExamResultForm = $(getExamResultFormId);
		var getExamResultBtn = $('#wlsm-get-exam-result-btn');
		var examResult = $('.wlsm-exam-result');
		getExamResultForm.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				return wlsmBeforeSubmit(getExamResultBtn);
			},
			success: function(response) {
				if(response.success) {
					examResult.html(response.data.html);
					$(window).scrollTop(examResult.offset().top - ($(window).height() - examResult.outerHeight(true)) / 2);
				} else {
					wlsmDisplayFormErrors(response, getExamResultFormId);
					examResult.html('');
				}
			},
			error: function(response) {
				wlsmDisplayFormError(response, getExamResultFormId, getExamResultBtn);
				examResult.html('');
			},
			complete: function(event, xhr, settings) {
				wlsmComplete(getExamResultBtn);
			}
		});

		// Get certificate.
		var getCertificateFormId = '#wlsm-get-certificate-form';
		var getCertificateForm = $(getCertificateFormId);
		var getCertificateBtn = $('#wlsm-get-certificate-btn');
		var certificate = $('.wlsm-certificate');
		getCertificateForm.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				return wlsmBeforeSubmit(getCertificateBtn);
			},
			success: function(response) {
				if(response.success) {
					certificate.html(response.data.html);
					$(window).scrollTop(certificate.offset().top - ($(window).height() - certificate.outerHeight(true)) / 2);
				} else {
					wlsmDisplayFormErrors(response, getCertificateFormId);
					certificate.html('');
				}
			},
			error: function(response) {
				wlsmDisplayFormError(response, getCertificateFormId, getCertificateBtn);
				certificate.html('');
			},
			complete: function(event, xhr, settings) {
				wlsmComplete(getCertificateBtn);
			}
		});

		// Get invoicehistory.
		var getInvoiceHistoryFormId = '#wlsm-get-invoice-history-form';
		var getInvoiceHistoryForm = $(getInvoiceHistoryFormId);
		var getInvoiceHistoryBtn = $('#wlsm-get-invoice-history-btn');
		var invoicehistory = $('.wlsm-invoice-history');
		getInvoiceHistoryForm.ajaxForm({
			beforeSubmit: function(arr, $form, options) {
				return wlsmBeforeSubmit(getInvoiceHistoryBtn);
			},
			success: function(response) {
				if(response.success) {
					invoicehistory.html(response.data.html);
					$(window).scrollTop(invoicehistory.offset().top - ($(window).height() - invoicehistory.outerHeight(true)) / 2);
				} else {
					wlsmDisplayFormErrors(response, getInvoiceHistoryFormId);
					invoicehistory.html('');
				}
			},
			error: function(response) {
				wlsmDisplayFormError(response, getInvoiceHistoryFormId, getInvoiceHistoryBtn);
				invoicehistory.html('');
			},
			complete: function(event, xhr, settings) {
				wlsmComplete(getInvoiceHistoryBtn);
			}
		});

		// General Actions.

		// Get school classes.
		$(document).on('change', '#wlsm_school', function() {
			var schoolId = this.value;
			var sessionId = $('#wlsm_session').val();
			var nonce = $(this).data('nonce');
			var classes = $('.wlsm_school_class');
			var sectionsExist = $(this).data('sections');

			// School registration form variables
			var dob                         = $('#registration_dob');
			var religion                    = $('#registration_religion');
			var caste                       = $('#registration_caste');
			var blood_group                 = $('#registration_blood_group');
			var phone                       = $('#registration_phone');
			var city                        = $('#registration_city');
			var state                       = $('#registration_state');
			var country                     = $('#registration_country');
			var transport                   = $('#registration_transport');
			var parent_detail               = $('#registration_parent_detail');
			var student_login               = $('#registration_student_login');
			var parent_login                = $('#registration_parent_login');
			var id_number                   = $('#registration_id');
			var survey                      = $('#registration_survey');
			var medium                      = $('#registration_medium');
			var registration_school_details = $('#registration_school_details');
			var registration_birth_place    = $('#registration_birth_place');
			var registration_mother_tongue  = $('#registration_mother_tongue');
			var registration_dob_in_words   = $('#registration_dob_in_words');
			var registration_student_type   = $('#registration_student_type');
			var registration_activity       = $('#registration_activity');
			var registration_address        = $('#registration_address');
			var registration_student_photo  = $('#registration_student_photo');

			var registration_pen  				= $('#registration_pen');
			var registration_apaar  			= $('#registration_apaar');
			var registration_father_id_number  	= $('#registration_father_id_number');
			var registration_mother_id_number  	= $('#registration_mother_id_number');

			$('div.wlsm-text-danger').remove();
			if(schoolId && nonce) {
				if(sectionsExist) {
					var sections = $('#wlsm_section');
					var firstOptionLabelSections = sections.find('option[value=""]').first().html();
					firstOptionLabelSections = '<option value="">' + firstOptionLabelSections + '</option>';
				}

				var firstOptionLabel = classes.find('option[value=""]').first().html();
				firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

				var data = 'action=wlsm-p-get-school-classes&nonce=' + nonce + '&school_id=' + schoolId;
				if(sessionId) {
					data += '&session_id=' + sessionId
				}
				if(classes.data('all-classes')) {
					data += '&all_classes=1';
				}
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [firstOptionLabel];
						res.forEach(function(item) {
							if (item.class.label !== '') {
								var option = '<option value="' + item.class.ID + '">' + item.class.label + '</option>';
								options.push(option);
							}
						});
						dob           = (res[0].dob !== true) ? dob.hide() : dob.fadeIn();
						religion      = (res[0].religion !== true) ? religion.hide() : religion.fadeIn();
						caste         = (res[0].caste !== true) ? caste.hide() : caste.fadeIn();
						blood_group   = (res[0].blood_group !== true) ? blood_group.hide() : blood_group.fadeIn();
						phone         = (res[0].phone !== true) ? phone.hide() : phone.fadeIn();
						city          = (res[0].city !== true) ? city.hide() : city.fadeIn();
						state         = (res[0].state !== true) ? state.hide() : state.fadeIn();
						country       = (res[0].country !== true) ? country.hide() : country.fadeIn();
						transport     = (res[0].transport !== true) ? transport.hide() : transport.fadeIn();
						parent_detail = (res[0].parent_detail !== true) ? parent_detail.hide() : parent_detail.fadeIn();
						student_login = (res[0].student_login !== true) ? student_login.hide() : student_login.fadeIn();
						parent_login  = (res[0].parent_login !== true) ? parent_login.hide() : parent_login.fadeIn();
						id_number     = (res[0].id_number !== true) ? id_number.hide() : id_number.fadeIn();
						survey        = (res[0].survey    !== true) ? survey   .hide() : survey   .fadeIn();
						medium        = (res[0].medium    !== true) ? medium   .hide() : medium   .fadeIn();
						registration_school_details = (res[0].school_details !== true) ? registration_school_details.hide() : registration_school_details.fadeIn();
						registration_birth_place    = (res[0].birth_place !== true) ? registration_birth_place.hide() : registration_birth_place.fadeIn();
						registration_mother_tongue  = (res[0].mother_tongue !== true) ? registration_mother_tongue.hide() : registration_mother_tongue.fadeIn();
						registration_dob_in_words   = (res[0].dob_in_words !== true) ? registration_dob_in_words.hide() : registration_dob_in_words.fadeIn();
						registration_student_type   = (res[0].student_type !== true) ? registration_student_type.hide() : registration_student_type.fadeIn();
						registration_activity       = (res[0].activity !== true) ? registration_activity.hide() : registration_activity.fadeIn();
						registration_address        = (res[0].address !== true) ? registration_address.hide() : registration_address.fadeIn();
						registration_student_photo  = (res[0].student_photo !== true) ? registration_student_photo.hide() : registration_student_photo.fadeIn();

						registration_pen  				= (res[0].pen !== true) ? registration_pen.hide() : registration_pen.fadeIn();
						registration_apaar  			= (res[0].apaar !== true) ? registration_apaar.hide() : registration_apaar.fadeIn();
						registration_father_id_number  	= (res[0].father_id_number !== true) ? registration_father_id_number.hide() : registration_father_id_number.fadeIn();
						registration_mother_id_number  	= (res[0].mother_id_number !== true) ? registration_mother_id_number.hide() : registration_mother_id_number.fadeIn();

						classes.html(options);
						if(sectionsExist) {
							sections.html([firstOptionLabelSections]);
						}
					}
				});
			} else {
				classes.html([firstOptionLabel]);
				if(sectionsExist) {
					sections.html([firstOptionLabelSections]);
				}
			}
		});

		$('#wlsm_attendance_year_month').Zebra_DatePicker({
			format: 'F Y',
			readonly_element: false,
			show_clear_date: true,
			disable_time_picker: true
		});


		// Staff: View attendance.
		var viewAttendanceFormId = '#wlsm-view-attendance-form';
		var viewAttendanceForm = $(viewAttendanceFormId);
		var viewAttendanceBtn = $('#wlsm-view-attendance-btn');

		$(document).on('click', '#wlsm-view-attendance-btn', function(e) {

			var studentsAttendance = $('.wlsm-students-attendance');

			var classId = $('#wlsm_class').val();
			var sectionId = $('#wlsm_section').val();
			var subjectId = $('#wlsm_subject').val();
			var schoolId = $('#wlsm_school_id').val();
			var studentId = $('#wlsm_student_id').val();
			var subjectId = $('#wlsm_subject').val();
			var sessionId = $('#wlsm_session_id').val();
			var attendance_by = $("input[name='attendance_by']:checked").val();
			var yearMonth = $('#wlsm_attendance_year_month').val();
			var nonce = $(this).data('nonce');
			var data = {};
			data['class_id'] = classId;
			data['section_id'] = sectionId;
			data['session_id'] = sessionId;
			data['school_id'] = schoolId;
			data['student_id'] = studentId;
			data['subject_id']  = subjectId;
			data['attendance_by'] = attendance_by;
			data['year_month'] = yearMonth;
			data['nonce'] = nonce;
			data['action'] = 'wlsm-view-attendance-student';

			if(nonce) {
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					beforeSend: function() {
						return wlsmBeforeSubmit(viewAttendanceBtn);
					},

					success: function(response) {
						if(response.success) {
							studentsAttendance.html(response.data.html);
						} else {
							wlsmDisplayFormErrors(response, viewAttendanceFormId);
						}
					},
					error: function(response) {
						wlsmDisplayFormError(response, viewAttendanceFormId, viewAttendanceBtn);
					},
					complete: function(event, xhr, settings) {
						wlsmComplete(viewAttendanceBtn);
					},
				});
			} else {
				studentsAttendance.html('');
			}
		});

		// Get class subjects.
		$(document).on('change', '.wlsm_school_class_subject', function() {
			var schoolId = $('#school_id').val();
			var classId = this.value;
			var nonce = $(this).data('nonce');
			var subjects = $('#wlsm_class_subject');

			$('div.wlsm-text-danger').remove();
			if(schoolId && classId && nonce) {
				var firstOptionLabel = subjects.find('option[value=""]').first().html();
				firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

				var data = 'action=wlsm-p-get-class-subjects&nonce=' + nonce + '&school_id=' + schoolId + '&class_id=' + classId;
				if(subjects.data('all-subjects')) {
					data += '&all_subjects=1';
				}
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [firstOptionLabel];
						res.forEach(function(item) {
							var option = '<option value="' + item.subject.ID + '">' + item.subject.label + '</option>';
							options.push(option);
						});
						subjects.html(options);
					}
				});
			} else {
				subjects.html([firstOptionLabel]);
			}
		});


		// Get subject chapter.
		$(document).on('change', '#wlsm_class_subject', function() {
			var schoolId = $('#school_id').val();
			var subjectId = this.value;
			var nonce = $(this).data('nonce');
			var chapter = $('#wlsm_chapter');

			$('div.wlsm-text-danger').remove();
			if(schoolId && subjectId && nonce) {
				var firstOptionLabel = chapter.find('option[value=""]').first().html();
				firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

				var data = 'action=wlsm-p-get-subject-chapter&nonce=' + nonce + '&school_id=' + schoolId + '&subject_id=' + subjectId;
				if(chapter.data('all-chapter')) {
					data += '&all_chapter=1';
				}
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [firstOptionLabel];
						res.forEach(function(item) {
							var option = '<option value="' + item.ID + '">' + item.title + '</option>';
							options.push(option);
						});
						chapter.html(options);
					}
				});
			} else {
				chapter.html([firstOptionLabel]);
			}
		});

		// Get class sections.
		$(document).on('change', '.wlsm_school_class', function() {
			var schoolId = $('#wlsm_school').val();
			var classId = this.value;
			var nonce = $(this).data('nonce');
			var sections = $('#wlsm_section');



			$('div.wlsm-text-danger').remove();
			if(schoolId && classId && nonce) {
				var firstOptionLabel = sections.find('option[value=""]').first().html();
				// firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

				var data = 'action=wlsm-p-get-class-sections&nonce=' + nonce + '&school_id=' + schoolId + '&class_id=' + classId;
				if(sections.data('all-sections')) {
					data += '&all_sections=1';
				}
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [firstOptionLabel];
						res.forEach(function(item) {
							var option = '<option value="' + item.ID + '">' + item.label + '</option>';
							options.push(option);
						});
						sections.html(options);
					}
				});
			} else {
				sections.html([firstOptionLabel]);
			}
		});

		$(document).on('change', '.wlsm_school_class_subject', function() {
			var schoolId = $('#wlsm_school').val();
			var classId = this.value;
			var nonce = $(this).data('nonce');
			var subjectsContainer = $('#wlsm_subjects');

			$('div.wlsm-text-danger').remove();
			if (schoolId && classId && nonce) {
				var data = 'action=wlsm-p-get-class-subjects&nonce=' + nonce + '&school_id=' + schoolId + '&class_id=' + classId;
				if (subjectsContainer.data('all-subjects')) {
					data += '&all_subjects=1';
				}
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var checkboxes = [];
						var allChecked = true;
						res.forEach(function(item) {
							var checkbox = '<label><input type="checkbox" name="subjects[]" value="' + item.subject.ID + '"';
							if (item.auto_select_subjects) {
								checkbox += ' checked disabled';
							} else {
								allChecked = false;
							}
							checkbox += '> ' + item.subject.label + '</label><br>';
							checkboxes.push(checkbox);
						});
						if (!allChecked) {
							subjectsContainer.html('<label><input type="checkbox" id="select_all_subjects"> Select All</label><br>' + checkboxes.join(''));
						} else {
							subjectsContainer.html(checkboxes.join(''));
						}
					}
				});
			} else {
				subjectsContainer.html('');
			}
		});

		// Handle "Select All" functionality
		$(document).on('change', '#select_all_subjects', function() {
			var isChecked = $(this).is(':checked');
			$('#wlsm_subjects input[type="checkbox"]').prop('checked', isChecked);
		});

		// Get class activity.
		$(document).on('change', '.wlsm_school_class_activity', function() {
			var schoolId = $('#wlsm_school').val();
			var classId = this.value;
			var nonce = $(this).data('nonce');
			var activityContainer = $('#wlsm_activity');

			$('div.wlsm-text-danger').remove();
			if (schoolId && classId && nonce) {
				var data = 'action=wlsm-p-get-class-activity&nonce=' + nonce + '&school_id=' + schoolId + '&class_id=' + classId;
				if (activityContainer.data('all-activity')) {
					data += '&all_activity=1';
				}
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var checkboxes = [];
						res.forEach(function(item) {
							var checkbox = '<label><input type="checkbox" name="activities[]" value="' + item.ID + '"> ' + item.title + ' (' + item.fees + ')</label><br>';
							checkboxes.push(checkbox);
						});
						activityContainer.html('<label><input type="checkbox" id="select_all_activities"> Select All</label><br>' + checkboxes.join(''));
					}
				});
			} else {
				activityContainer.html('');
			}
		});

		// Handle "Select All" functionality for activities
		$(document).on('change', '#select_all_activities', function() {
			var isChecked = $(this).is(':checked');
			$('#wlsm_activity input[type="checkbox"]').prop('checked', isChecked);
		});

		// Get class fees.
			$(document).on('change', '.wlsm_get_class_fees', function() {
				var schoolId = $('#wlsm_school').val();
				var student_type = $('#wlsm_student_type').val();
				var classId = this.value;
				var nonce = $(this).data('nonce');
				var sessionId = $(this).data('session');
				var feesElement = $('#wlsm_fees'); // Renamed to feesElement to avoid conflict
				var totalAmount = 0;

				$('div.wlsm-text-danger').remove();
				if (schoolId && classId && nonce) {
					var data = 'action=wlsm-p-get-class-fees&nonce=' + nonce + '&session_id='+ sessionId + '&school_id=' + schoolId + '&class_id=' + classId + '&student_type=' + student_type;
					if (feesElement.data('all-fees')) {
						data += '&all_fees=1';
					}
					$.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							var months = res.session_months;
							var feesData = res.fees; // Renamed variable to avoid conflict

							// Check if feesData is an array
							if (!Array.isArray(feesData)) {
								console.error('feesData is not an array:', feesData);
								feesElement.html('<div class="wlsm-text-danger">Error: Invalid fees data received.</div>');
								return;
							}

							var table = '<table><thead><tr><th>Fee Type </th><th>Period</th><th>Amount</th> <th>Session Total</th></tr></thead><tbody>';
							var feesTotal = 0;
							var totalAmount = 0; // Initialize totalAmount

							console.log(feesData);
														feesData.forEach(function(item) {
								var count = 1;
								feesTotal += parseFloat(item.amount);
								if (item.period === 'monthly') {
									count = months;
								} else if (item.period === 'quarterly') {
									count = Math.ceil(months / 3); // quarterly is 3 months
								} else if (item.period === 'half-yearly') {
									count = Math.ceil(months / 6);
								} else if (item.period === 'one-time') {
									count = 1;
								} else if (item.period === 'annually') {
									count = Math.ceil(months / 12);
								} else if (item.period === 'quadrimester') {
									count = Math.ceil(months / 4); // quadrimester is 4 months
								}

								// Format the period text to be human-readable
								var periodText = getFormattedPeriodText(item.period);

								var session_total = parseFloat(item.amount) * count;
								table += '<tr>' +
										 '<td>' + item.label + '</td>' +
										 '<td>' + periodText + '</td>' +
										 '<td>' + item.amount + '</td>' +
										 '<td>' + item.amount + ' × ' + count + ' = ' + session_total.toFixed(2) + '</td>' +
										 '</tr>';
								totalAmount += parseFloat(session_total);
							});

							// Add this function to translate period keys to readable text
							function getFormattedPeriodText(period) {
								switch(period) {
									case 'one-time':
										return 'One Time';
									case 'monthly':
										return 'Monthly';
									case 'quarterly':
										return 'Quarterly (3 Months)';
									case 'quadrimester':
										return 'Quadrimester (4 Months)';
									case 'half-yearly':
										return 'Half Yearly (6 Months)';
									case 'annually':
										return 'Annually (12 Months)';
									default:
										return period;
								}
							}

							table += '<tr><td><b>Total</b></td><td></td><td>' + feesTotal.toFixed(2) + '</td><td><b>' + totalAmount.toFixed(2) + '</b></td></tr>';
							table += '</tbody></table>';

							feesElement.html(table); // Use feesElement here
						}
					});
				} else {
					feesElement.html(''); // Use feesElement here
				}
			});

		// Get school routes vehicles.
		$(document).on('change', '#wlsm_school', function() {
			var schoolId = this.value;
			var nonce = $(this).data('routes-vehicles-nonce');
			var routesVehicles = $('#wlsm_route_vehicle');

			$('div.wlsm-text-danger').remove();
			if(schoolId && nonce) {
				var firstOptionLabel = routesVehicles.find('option[value=""]').first().html();
				firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

				var data = 'action=wlsm-p-get-school-routes-vehicles&nonce=' + nonce + '&school_id=' + schoolId;
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [firstOptionLabel];
						routesVehicles.html(options);
						routesVehicles.append(res.html);
					}
				});
			} else {
				routesVehicles.html([firstOptionLabel]);
			}
		});

		// Get school exams with published time table.
		$(document).on('change', '#wlsm_school_exams_time_table', function() {
			var schoolId = this.value;
			var nonce = $(this).data('nonce');
			var exams = $('#wlsm_school_exam');

			var firstOptionLabel = exams.find('option[value=""]').first().html();
			firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

			$('div.wlsm-text-danger').remove();
			if(schoolId && nonce) {
				var data = 'action=wlsm-p-get-school-exams-time-table&nonce=' + nonce + '&school_id=' + schoolId;
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [firstOptionLabel];
						res.forEach(function(item) {
							var option = '<option value="' + item.ID + '">' + item.label + '</option>';
							options.push(option);
						});
						exams.html(options);
					}
				});
			} else {
				exams.html([firstOptionLabel]);
			}
		});

		// Get school exams with published admit card.
		$(document).on('change', '#wlsm_school_exams_admit_card', function() {
			var schoolId = this.value;
			var nonce = $(this).data('nonce');
			var exams = $('#wlsm_school_exam');

			var firstOptionLabel = exams.find('option[value=""]').first().html();
			firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

			$('div.wlsm-text-danger').remove();
			if(schoolId && nonce) {
				var data = 'action=wlsm-p-get-school-exams-admit-card&nonce=' + nonce + '&school_id=' + schoolId;
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [firstOptionLabel];
						res.forEach(function(item) {
							var option = '<option value="' + item.ID + '">' + item.label + '</option>';
							options.push(option);
						});
						exams.html(options);
					}
				});
			} else {
				exams.html([firstOptionLabel]);
			}
		});

		// Get school exams with published result.
		$(document).on('change', '#wlsm_school_exams_result', function() {
			var schoolId = this.value;
			var nonce = $(this).data('nonce');
			var exams = $('#wlsm_school_exam');

			var firstOptionLabel = exams.find('option[value=""]').first().html();
			firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

			$('div.wlsm-text-danger').remove();
			if(schoolId && nonce) {
				var data = 'action=wlsm-p-get-school-exams-result&nonce=' + nonce + '&school_id=' + schoolId;
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [firstOptionLabel];
						res.forEach(function(item) {
							var option = '<option value="' + item.ID + '">' + item.label + '</option>';
							options.push(option);
						});
						exams.html(options);
					}
				});
			} else {
				exams.html([firstOptionLabel]);
			}
		});

		// Get school certificates.
		$(document).on('change', '#wlsm_school_certificate', function() {
			var schoolId = this.value;
			var nonce = $(this).data('nonce');
			var certificates = $('#wlsm_certificate');

			var firstOptionLabel = certificates.find('option[value=""]').first().html();
			firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

			$('div.wlsm-text-danger').remove();
			if(schoolId && nonce) {
				var data = 'action=wlsm-p-get-school-certificates&nonce=' + nonce + '&school_id=' + schoolId;
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [firstOptionLabel];
						res.forEach(function(item) {
							var option = '<option value="' + item.ID + '">' + item.label + '</option>';
							options.push(option);
						});
						certificates.html(options);
					}
				});
			} else {
				certificates.html([firstOptionLabel]);
			}
		});

		// Add classes to login form button.
		$('#wlsm-login-form input[type="submit"]').attr('class', 'button btn btn-primary')
		$('#wlsm-login-via-widget-form input[type="submit"]').attr('class', 'button btn btn-primary')

		// Student: View study material.
		$(document).on('click', '.wlsm-st-view-study-material', function(event) {
			var element = $(this);
			var studyMaterialId = element.data('study-material');
			var userID = element.data('user');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['study_material_id'] = studyMaterialId;
			data['user_id'] = userID;
			data['st-view-study-material-' + studyMaterialId] = nonce;
			data['action'] = 'wlsm-p-st-view-study-material';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				boxWidth: '900px',
				backgroundDismiss: true
			});
		});

		// Student: View homework.
		$(document).on('click', '.wlsm-st-view-homework', function(event) {
			var element = $(this);
			var homeworkId = element.data('homework');
			var userID = element.data('user-id');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['homework_id'] = homeworkId;
			data['user_id'] = userID;
			data['st-view-homework-' + homeworkId] = nonce;
			data['action'] = 'wlsm-p-st-view-homework';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				boxWidth: '900px',
				backgroundDismiss: true
			});
		});

		// Student: Join event.
		$(document).on('click', '.wlsm-join-event-btn', function(event) {
			var element = $(this);
			var eventId = element.data('event');
			var title = element.data('message-title');
			var nonce = element.data('nonce');
			var userID = element.data('user-id');
			var confirmMessage = $(this).data('confirm');

			var data = {};
			data['event_id'] = eventId;
			data['user_id'] = userID;
			data['st-join-event-' + eventId] = nonce;
			data['action'] = 'wlsm-p-st-join-event';

			if(confirm(confirmMessage)) {
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(response) {
						if(response.success) {
							element.attr('disabled', true);
							element.html(response.data.replace_text);
							toastr.success(
								response.data.message,
								'',
								{
									timeOut: 600,
									fadeOut: 600,
									closeButton: true,
									progressBar: true,
									onHidden: function() {
										$('.wlsm-join-unjoin-event-box').load(location.href + " " + '.wlsm-join-unjoin-event', function () {});
									}
								}
							);
						} else {
							toastr.error(response.data);
						}
					},
					error: function(response) {
						toastr.error(response.status + ': ' + response.statusText);
					}
				});
			}
		});

		// Student: Unjoin event.
		$(document).on('click', '.wlsm-unjoin-event-btn', function(event) {
			var element = $(this);
			var eventId = element.data('event');
			var title = element.data('message-title');
			var nonce = element.data('nonce');
			var userID = element.data('user-id');
			var confirmMessage = $(this).data('confirm');

			var data = {};
			data['event_id'] = eventId;
			data['user_id'] = userID;
			data['st-unjoin-event-' + eventId] = nonce;
			data['action'] = 'wlsm-p-st-unjoin-event';

			if(confirm(confirmMessage)) {
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(response) {
						if(response.success) {
							$('.wlsm-joined-message').remove();
							element.attr('disabled', true);
							element.html(response.data.replace_text);
							toastr.success(
								response.data.message,
								'',
								{
									timeOut: 600,
									fadeOut: 600,
									closeButton: true,
									progressBar: true,
									onHidden: function() {
										$('.wlsm-join-unjoin-event-box').load(location.href + " " + '.wlsm-join-unjoin-event', function () {});
									}
								}
							);
						} else {
							toastr.error(response.data);
						}
					},
					error: function(response) {
						toastr.error(response.status + ': ' + response.statusText);
					}
				});
			}
		});

		// Student: Submit leave request.
		// Leave start date.
		$('#wlsm_leave_start_date').Zebra_DatePicker({
			format: wlsmdateformat,
			readonly_element: false,
			show_clear_date: true,
			disable_time_picker: true,
			direction: true,
			pair: $('#wlsm_leave_end_date')
		});

		// Leave end date.
		$('#wlsm_leave_end_date').Zebra_DatePicker({
			format: wlsmdateformat,
			readonly_element: false,
			show_clear_date: true,
			disable_time_picker: true,
			direction: 1
		});

		// Leave for single or multiple days.
		var leaveEndDate = $('.wlsm_leave_end_date');
		var multipleDays = $('input[type="radio"][name="multiple_days"]:checked').val();
		if('1' === multipleDays) {
			leaveEndDate.show();
		} else {
			leaveEndDate.hide();
		}

		$(document).on('change', 'input[type="radio"][name="multiple_days"]', function() {
			var multipleDays = this.value;
			var leaveStartDate = $('#wlsm_leave_start_date');
			var leaveStartDateLabel = $('label[for="wlsm_leave_start_date"]');
			if('1' === multipleDays) {
				leaveStartDateLabel.html(leaveStartDate.data('multiple'));
				leaveStartDate.attr('placeholder', leaveStartDate.data('multiple'));
				leaveEndDate.fadeIn();
			} else {
				leaveStartDateLabel.html(leaveStartDate.data('single'));
				leaveStartDate.attr('placeholder', leaveStartDate.data('single'));
				leaveEndDate.fadeOut();
			}
		});

		// Submit homework .
		var submitHomeworkFormId = '#wlsm-submit-student-homework-submission-form';
		var submitHomeworkForm = $(submitHomeworkFormId);
		var submitHomeworkBtn = $('#wlsm-submit-student-homework-submission-btn');
		$(document).on('click', '#wlsm-submit-student-homework-submission-btn', function (e) {
			e.preventDefault();
			var confirmMessage = $(this).data('confirm');
			if (confirm(confirmMessage)) {
				submitHomeworkForm.ajaxSubmit({
					beforeSubmit: function (arr, $form, options) {
						return wlsmBeforeSubmit(submitHomeworkBtn);
					},
					success: function (response) {
						if (response.success) {
							toastr.success(response.data.message);
							window.location.reload();
						} else {
							wlsmDisplayFormErrors(response, submitHomeworkFormId);
						}
					},
					error: function (response) {
						wlsmDisplayFormError(response, submitHomeworkFormId, submitHomeworkBtn);
						window.location.reload();
					},
					complete: function (event, xhr, settings) {
						wlsmComplete(submitHomeworkBtn);
					}
				});
			}
		});

				// Submit ticket.
		var submitTicketFormId = '#addTicketForm';
		var submitTicketForm = $(submitTicketFormId);
		var submitTicketBtn = $('#wlsm-submit-ticket-btn');

		$(document).on('click', '#wlsm-submit-ticket-btn', function (e) {
			e.preventDefault();
			var confirmMessage = $(this).data('confirm');
			if (confirm(confirmMessage)) {
				submitTicketForm.ajaxSubmit({
					beforeSubmit: function () {
						return wlsmBeforeSubmit(submitTicketBtn);
					},
					success: function (response) {
						if (response.success) {
							toastr.success(response.data.message);
							window.location.reload();
						} else {
							wlsmDisplayFormErrors(response, submitTicketFormId);
						}
					},
					error: function (response) {
						wlsmDisplayFormError(response, submitTicketFormId, submitTicketBtn);
						window.location.reload();
					},
					complete: function () {
						wlsmComplete(submitTicketBtn);
					}
				});
			}
		});


		// Submit leave request.
		var submitLeaveRequestFormId = '#wlsm-submit-student-leave-request-form';
		var submitLeaveRequestForm = $(submitLeaveRequestFormId);
		var submitLeaveRequestBtn = $('#wlsm-submit-student-leave-request-btn');
		$(document).on('click', '#wlsm-submit-student-leave-request-btn', function(e) {
			e.preventDefault();
			var confirmMessage = $(this).data('confirm');
			if(confirm(confirmMessage)) {
				submitLeaveRequestForm.ajaxSubmit({
					beforeSubmit: function(arr, $form, options) {
						return wlsmBeforeSubmit(submitLeaveRequestBtn);
					},
					success: function(response) {
						if(response.success) {
							toastr.success(response.data.message);
							window.location.reload();
						} else {
							wlsmDisplayFormErrors(response, submitLeaveRequestFormId);
						}
					},
					error: function(response) {
						wlsmDisplayFormError(response, submitLeaveRequestFormId, submitLeaveRequestBtn);
						window.location.reload();
					},
					complete: function(event, xhr, settings) {
						wlsmComplete(submitLeaveRequestBtn);
					}
				});
			}
		});

		// Student: Print ticket.
		$(document).on('click', '.wlsm-st-ticket', function(event) {
			var element = $(this);
			var ticketId = element.data('ticket-id');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['ticket_id'] = ticketId;
			data['st-ticket-' + ticketId] = nonce;
			data['action'] = 'wlsm-p-st-ticket';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});



		// Student: Print invoice payment.
		$(document).on('click', '.wlsm-st-print-invoice-payment', function(event) {
			var element = $(this);
			var paymentId = element.data('invoice-payment');
			var studentId = element.data('student');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['payment_id'] = paymentId;
			data['student_id'] = studentId;
			data['st-print-invoice-payment-' + paymentId] = nonce;
			data['action'] = 'wlsm-p-st-print-invoice-payment';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});

		// Parent: Print invoice payment.
		$(document).on('click', '.wlsm-pr-print-invoice-payment', function(event) {
			var element = $(this);
			var paymentId = element.data('invoice-payment');
			var studentId = element.data('student');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['payment_id'] = paymentId;
			data['student_id'] = studentId;
			data['pr-print-invoice-payment-' + paymentId] = nonce;
			data['action'] = 'wlsm-p-pr-print-invoice-payment';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});

		// Student: Print ID card.
		$(document).on('click', '.wlsm-st-print-id-card', function(event) {
			var element = $(this);
			var userId = element.data('id-card');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['st-print-id-card-' + userId] = nonce;
			data['user_id'] = userId;
			data['action'] = 'wlsm-p-st-print-id-card';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});

		// Parent: Print ID card.
		$(document).on('click', '.wlsm-pr-print-id-card', function(event) {
			var element = $(this);
			var studentId = element.data('id-card');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['student_id'] = studentId;
			data['pr-print-id-card-' + studentId] = nonce;
			data['action'] = 'wlsm-p-pr-print-id-card';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});

		// Student: Print class time table.
		$(document).on('click', '.wlsm-st-print-class-time-table', function(event) {
			var element = $(this);
			var sectionId = element.data('class-time-table');
			var userID = element.data('user');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['section_id'] = sectionId;
			data['user_id'] = userID;
			data['st-print-class-time-table-' + sectionId] = nonce;
			data['action'] = 'wlsm-p-st-print-class-time-table';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				boxWidth: '90%',
				backgroundDismiss: true
			});
		});

		// Parent: Print class time table.
		$(document).on('click', '.wlsm-pr-print-class-time-table', function(event) {
			var element = $(this);
			var sectionId = element.data('class-time-table');
			var studentId = element.data('student');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['section_id'] = sectionId;
			data['student_id'] = studentId;
			data['pr-print-class-time-table-' + sectionId] = nonce;
			data['action'] = 'wlsm-p-pr-print-class-time-table';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				boxWidth: '90%',
				backgroundDismiss: true
			});
		});

		// Student: Print exam time table.
		$(document).on('click', '.wlsm-st-print-exam-time-table', function(event) {
			var element = $(this);
			var examId = element.data('exam-time-table');
			var userID = element.data('user-id');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['exam_id'] = examId;
			data['user_id'] = userID;
			data['st-print-exam-time-table-' + examId] = nonce;
			data['action'] = 'wlsm-p-st-print-exam-time-table';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});

		// Student: Print exam admit card.
		$(document).on('click', '.wlsm-st-print-exam-admit-card', function(event) {
			var element = $(this);
			var examAdmitCardId = element.data('exam-admit-card');
			var userID = element.data('user-id');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['admit_card_id'] = examAdmitCardId;
			data['user_id'] = userID;
			data['st-print-exam-admit-card-' + examAdmitCardId] = nonce;
			data['action'] = 'wlsm-p-st-print-exam-admit-card';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});

		// Student: Print exam results.
		$(document).on('click', '.wlsm-st-print-exam-results', function(event) {
			var element = $(this);
			var admitCardId = element.data('exam-results');
			var userID = element.data('user-id');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['admit_card_id'] = admitCardId;
			data['user_id'] = userID;
			data['st-print-exam-results-' + admitCardId] = nonce;
			data['action'] = 'wlsm-p-st-print-exam-results';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});

		// Student: Print exam results.
		$(document).on('click', '.wlsm-result-subject-wise', function(event) {
			var element = $(this);
			var admitCardId = element.data('student');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['student_id'] = admitCardId;
			data['result-subject-wise-' + admitCardId] = nonce;
			data['action'] = 'wlsm-p-result-subject-wise';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});

		// Parent: Print exam results.
		$(document).on('click', '.wlsm-pr-print-exam-results', function(event) {
			var element = $(this);
			var admitCardId = element.data('exam-results');
			var studentId = element.data('student');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['admit_card_id'] = admitCardId;
			data['student_id'] = studentId;
			data['pr-print-exam-results-' + admitCardId] = nonce;
			data['action'] = 'wlsm-p-pr-print-exam-results';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});

		// Student: Print results assessment.
		$(document).on('click', '.wlsm-st-print-results-assessment', function(event) {
			var element = $(this);
			var studentId = element.data('student');
			var userID = element.data('user-id');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['student_id'] = studentId;
			data['user_id'] = userID;
			data['st-print-results-assessment-' + studentId] = nonce;
			data['action'] = 'wlsm-p-st-print-results-assessment';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});

		// Student: Print results subject-wise.
		$(document).on('click', '.wlsm-st-print-results-subject-wise', function(event) {
			var element = $(this);
			var studentId = element.data('student');
			var userID = element.data('user-id');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['student_id'] = studentId;
			data['user_id'] = userID;
			data['st-print-results-subject-wise-' + studentId] = nonce;
			data['action'] = 'wlsm-p-st-print-results-subject-wise';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'xlarge',
				backgroundDismiss: true
			});
		});

		// Shortcode: Print exam time table.
		$(document).on('click', '.wlsm-print-exam-time-table', function(event) {
			var element = $(this);
			var schoolId = element.data('school');
			var examId = element.data('exam-time-table');
			var title = element.data('message-title');
			var nonce = element.data('nonce');

			var data = {};
			data['school_id'] = schoolId;
			data['exam_id'] = examId;
			data['print-exam-time-table-' + examId] = nonce;
			data['action'] = 'wlsm-p-print-exam-time-table';

			$.dialog({
				title: title,
				content: function() {
					var self = this;
					return $.ajax({
						data: data,
						url: wlsmajaxurl,
						type: 'POST',
						success: function(res) {
							self.setContent(res.data.html);
						}
					});
				},
				theme: 'bootstrap',
				useBootstrap: false,
				columnClass: 'large',
				backgroundDismiss: true
			});
		});

		// Print.
		function wlsmPrint(targetId, title, styleSheets, css = '') {
			var target = $(targetId).html();

			var frame = $('<iframe />');
			frame[0].name = 'frame';
			frame.css({ 'position': 'absolute', 'top': '-1000000px' });

			var that = frame.appendTo('body');
			var frameDoc = frame[0].contentWindow ? frame[0].contentWindow : frame[0].contentDocument.document ? frame[0].contentDocument.document : frame[0].contentDocument;
			frameDoc.document.open();

			// Create a new HTML document.
			frameDoc.document.write('<html><head>' + title);
			frameDoc.document.write('</head><body>');

			// Append the external CSS file.
			styleSheets.forEach(function(styleSheet, index) {
				$(that).contents().find('head').append('<link href="' + styleSheet + '" rel="stylesheet" type="text/css" referrerpolicy="origin" />');
			});

			if(css) {
				frameDoc.document.write('<style>' + css + '</style>');
			}

			// Append the target.
			frameDoc.document.write(target);
			frameDoc.document.write('</body></html>');
			frameDoc.document.close();

			setTimeout(function () {
				window.frames["frame"].focus();
				window.frames["frame"].print();
				frame.remove();
			}, 1000);
		}

		// Print ID card.
		$(document).on('click', '#wlsm-print-id-card-btn', function() {
			var targetId = '#wlsm-print-id-card';
			var title = $(this).data('title');
			if(title) {
				title = '<title>' + title  + '</title>';
			}
			var styleSheets = $(this).data('styles');

			wlsmPrint(targetId, title, styleSheets);
		});

		// Print payment.
		$(document).on('click', '#wlsm-print-invoice-payment-btn', function() {
			var targetId = '#wlsm-print-invoice-payment';
			var title = $(this).data('title');
			if(title) {
				title = '<title>' + title  + '</title>';
			}
			var styleSheets = $(this).data('styles');

			wlsmPrint(targetId, title, styleSheets);
		});

		// Print exam time table.
		$(document).on('click', '#wlsm-print-exam-time-table-btn', function() {
			var targetId = '#wlsm-print-exam-time-table';
			var title = $(this).data('title');
			if(title) {
				title = '<title>' + title  + '</title>';
			}
			var styleSheets = $(this).data('styles');

			wlsmPrint(targetId, title, styleSheets);
		});

		// Print exam admit card.
		$(document).on('click', '#wlsm-print-exam-admit-card-btn', function() {
			var targetId = '#wlsm-print-exam-admit-card';
			var title = $(this).data('title');
			if(title) {
				title = '<title>' + title  + '</title>';
			}
			var styleSheets = $(this).data('styles');

			wlsmPrint(targetId, title, styleSheets);
		});

		// Print exam results.
		$(document).on('click', '#wlsm-print-exam-results-btn', function() {
			var targetId = '#wlsm-print-exam-results';
			var title = $(this).data('title');
			if(title) {
				title = '<title>' + title  + '</title>';
			}
			var styleSheets = $(this).data('styles');

			wlsmPrint(targetId, title, styleSheets);
		});

		// Print results assessment.
		$(document).on('click', '#wlsm-print-result-assessment-btn', function() {
			var targetId = '#wlsm-print-results-assessment';
			var title = $(this).data('title');
			if(title) {
				title = '<title>' + title  + '</title>';
			}
			var styleSheets = $(this).data('styles');

			wlsmPrint(targetId, title, styleSheets);
		});

		// Print results subject-wise.
		$(document).on('click', '#wlsm-print-result-subject-wise-btn', function() {
			var targetId = '#wlsm-print-results-subject-wise';
			var title = $(this).data('title');
			if(title) {
				title = '<title>' + title  + '</title>';
			}
			var styleSheets = $(this).data('styles');

			wlsmPrint(targetId, title, styleSheets);
		});

		// Print certficate.
		$(document).on('click', '#wlsm-print-certificate-btn', function() {
			var targetId = '#wlsm-print-certificate';
			var title = $(this).data('title');
			if(title) {
				title = '<title>' + title  + '</title>';
			}
			var styleSheets = $(this).data('styles');
			var css = $(this).data('css');

			wlsmPrint(targetId, title, styleSheets, css);
		});

		// Print class timetable.
		$(document).on('click', '#wlsm-print-class-timetable-btn', function() {
			var targetId = '#wlsm-print-class-timetable';
			var title = $(this).data('title');
			if(title) {
				title = '<title>' + title  + '</title>';
			}
			var styleSheets = $(this).data('styles');

			wlsmPrint(targetId, title, styleSheets);
		});

		// Get Get ratting form
		$(document).on('click', '.wlsm-st-print-staff-ratting', function (e) {
			e.preventDefault();
			var viewRattingBtn = $(this);

			var studentRattingForm = $('.wlsm-student-ratting-form');

			var live_class_id = $(this).data('class');
			var nonce = $(this).data('nonce');

			var data = {};
			data['live_class_id'] = live_class_id;
			data['nonce'] = nonce;
			data['action'] = 'wlsm-p-st-print-staff-ratting';

			if (nonce) {
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					beforeSend: function () {
						return wlsmBeforeSubmit(viewRattingBtn);
					},
					success: function (response) {
						if (response.success) {
							studentRattingForm.html(response.data.html);
							$(window).scrollTop(studentRattingForm.offset().top - ($(window).height() - studentRattingForm.outerHeight(true)) / 2);
						}
					},
					complete: function (event, xhr, settings) {
						wlsmComplete(viewRattingBtn);
					},
				});
			} else {
				studentRattingForm.html('');
			}
		});

		// Student: Join event.
		$(document).on('click', '#wlsm-submit-ratting-btn', function (event) {
			var element = $(this);
			var classId = $('input[name="live_class_id"]').val();
			var message = $('#wlsm_message').val();
			var starRatting = $('input[name="rating"]:checked').val();
			var nonce = $('input[name=nonce]').val();

			var data = {};
			data['class_id'] = classId;
			data['nonce'] = nonce;
			data['message'] = message;
			data['star_ratting'] = starRatting;
			data['action'] = 'wlsm-p-staff_class_ratting';

			$.ajax({
				data: data,
				url: wlsmajaxurl,
				type: 'POST',
				success: function (response) {
					if (response.success) {
						element.attr('disabled', true);
						element.html(response.data.replace_text);
						toastr.success(
							response.data.message,
							'',
							{
								timeOut: 600,
								fadeOut: 600,
								closeButton: true,
								progressBar: true,
								onHidden: function () {
									$('.wlsm-join-unjoin-event-box').load(location.href + " " + '.wlsm-join-unjoin-event', function () { });
								}
							}
						);
					} else {
						toastr.error(response.data);
					}
				},
				error: function (response) {
					toastr.error(response.status + ': ' + response.statusText);
				}
			});
		});

		// Submit ratting request.
		var submitRattingRequestFormId = '#wlsm-staff-class-ratting-form';
		var submitRattingRequestForm = $(submitRattingRequestFormId);
		var submitRattingRequestBtn = $('#wlsm-submit-ratting-btn');
		$(document).on('click', '#wlsm-submit-ratting-btn', function (e) {
			e.preventDefault();
			// var confirmMessage = ('Are you Sure?');
			// if (confirm(confirmMessage)) {

			submitRattingRequestForm.ajaxSubmit({
				beforeSubmit: function (arr, $form, options) {
					return wlsmBeforeSubmit(submitRattingRequestBtn);
				},
				success: function (response) {
					if (response.success) {
						toastr.success(response.data.message);
						window.location.reload();
					} else {
						wlsmDisplayFormErrors(response, submitRattingRequestFormId);
					}
				},
				error: function (response) {
					wlsmDisplayFormError(response, submitRattingRequestFormId, submitRattingRequestBtn);
					window.location.reload();
				},
				complete: function (event, xhr, settings) {
					wlsmComplete(submitRattingRequestBtn);
				}
			});
			// }
		});


		// Student selector change handler
		$(document).on('change', '.wlsm-select-student', function(e) {
			var studentId = $(this).val();
			var nonce = $(this).data('nonce');

			// Show loading
			$(this).addClass('loading');

			// Make AJAX request
			$.ajax({
				data: {
					action: 'wlsm-p-switch-student',
					student_id: studentId,
					nonce: nonce
				},
				url: wlsmajaxurl,
				type: 'POST',
				beforeSend: function() {
					// Disable select while loading
					$('.wlsm-select-student').prop('disabled', true);
				},
				success: function(response) {
					if (response.success) {
						// Reload page with new student data
						window.location.reload();
					} else {
						// Show error message
						toastr.error(response.data.message);
					}
				},
				error: function(xhr) {
					// Show error message
					toastr.error(wlsmadminjs.something_went_wrong);
				},
				complete: function() {
					// Remove loading state
					$('.wlsm-select-student').removeClass('loading').prop('disabled', false);
				}
			});
		});


		const modal = document.querySelector(".modal");
		const trigger = document.querySelector(".trigger");
		const closeButton = document.querySelector(".close-button");

		function toggleModal() {
			modal.classList.toggle("show-modal");
		}

		function windowOnClick(event) {
			if (event.target === modal) {
				toggleModal();
			}
		}

		// trigger.addEventListener("click", toggleModal);
		// closeButton.addEventListener("click", toggleModal);
		// window.addEventListener("click", windowOnClick);

	});


	var sBox = $('#sib_box');

	$(document).on('click', '.wlsm-add-sibling-btn', function() {
		var studentBox = $('.sbox:last').data('sbox');

		if(studentBox === undefined) {
			studentBox = 1;
		}

		studentBox++;
		var id = studentBox;

		var genderList     = Object.values(wlsmgenderlist);
		var blogdGroupList = Object.values(wlsmbloodgrouplist);
		var classList      = Object.values(wlsmclasslist);
		var stypeList      = Object.values(studentTypeList);

		$(`wlsm_date_of_birth`).Zebra_DatePicker({
			format: wlsmdateformat,
			readonly_element: false,
			show_clear_date: true,
			disable_time_picker: true,
			view: 'years',
			direction: false
		});

		$(document).on('change', `#wlsm_school_class_${id}`, function() {
			var schoolId = $('#wlsm_school').val();
			var classId = this.value;
			var nonce = $(this).data('nonce');
			var sections = $(`#wlsm_section_${id}`);

			$('div.wlsm-text-danger').remove();
			if(schoolId && classId && nonce) {
				var firstOptionLabel = sections.find('option[value=""]').first().html();
				// firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

				var data = 'action=wlsm-p-get-class-sections&nonce=' + nonce + '&school_id=' + schoolId + '&class_id=' + classId;
				if(sections.data('all-sections')) {
					data += '&all_sections=1';
				}
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [firstOptionLabel];
						res.forEach(function(item) {
							var option = '<option value="' + item.ID + '">' + item.label + '</option>';
							options.push(option);
						});
						sections.html(options);
					}
				});
			} else {
				sections.html([firstOptionLabel]);
			}
		});

		// Get class subjects.
		$(document).on('change', `.wlsm_school_class_subject_${id}`, function() {
			var schoolId = $('#wlsm_school').val();
			var classId = this.value;
			var nonce = $(this).data('nonce');
			var subjects = $(`#wlsm_subjects_${id}`);
			var firstOptionLabel = subjects.find('option[value=""]').first().html();
			firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';
			$('div.wlsm-text-danger').remove();
			if(schoolId && classId && nonce) {


				var data = 'action=wlsm-p-get-class-subjects&nonce=' + nonce + '&school_id=' + schoolId + '&class_id=' + classId;
				if(subjects.data('all-subjects')) {
					data += '&all_subjects=1';
				}
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [];
						res.forEach(function(item) {
							// access object inside object.
							var option = '<option value="' + item.subject.ID + '">' + item.subject.label + '</option>';
							// var option = '<option value="' + item.ID + '">' + item.label + '</option>';
							options.push(option);
						});
						subjects.html(options);
						// subjects.SumoSelect('refresh');
					}
				});
			} else {
				subjects.html([firstOptionLabel]);
			}
		});

		// Get class activity.
		$(document).on('change', `.wlsm_school_class_activity_${id}`, function() {
			var schoolId = $('#wlsm_school').val();
			var classId = this.value;
			var nonce = $(this).data('nonce');
			var activity = $(`#wlsm_activity_${id}`);

			$('div.wlsm-text-danger').remove();
			if(schoolId && classId && nonce) {
				var firstOptionLabel = activity.find('option[value=""]').first().html();
				firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

				var data = 'action=wlsm-p-get-class-activity&nonce=' + nonce + '&school_id=' + schoolId + '&class_id=' + classId;
				if(activity.data('all-activity')) {
					data += '&all_activity=1';
				}
				$.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						var options = [];
						res.forEach(function(item) {
							// access object inside object.
							var option = '<option value="' + item.ID + '">' + item.title + ' (' +item.fees + ')' + '</option>';
							// var option = '<option value="' + item.ID + '">' + item.label + '</option>';
							options.push(option);
						});
						activity.html(options);
						// activity.SumoSelect('refresh');
					}
				});
			} else {
				subjects.html([firstOptionLabel]);
			}
		});

		sBox.append(`
		<div class="wlsm-form-sub-heading wlsm-font-bold"> ${studentAdd} </div>

		<p class="wlsm-font-bold"> ${personalDetail} </p>

		<div class=" sbox" data-sbox="${id}">
			<div class="wlsm-row">
			<div class="wlsm-form-group wlsm-col-4">
				<label for="wlsm_name_${id}" class="wlsm-font-bold">
					<span class="wlsm-important">*</span> ${studentName}:
				</label>
				<input type="text" name="name_[${id}]" class="wlsm-form-control" id="wlsm_name_${id}" placeholder="Enter ${studentName}" value="">
			</div>
			<div class="wlsm-form-group wlsm-col-4">
				<label class="wlsm-font-bold wlsm-d-block">
					<span class="wlsm-important">*</span> ${Gender}:
				</label>
				<select name="genders_[${id}]" class="wlsm-form-control">
					${genderList.map((gender, index) => `
						<option value="${gender.toLowerCase()}" ${index === 0 ? 'selected' : ''}>${gender}</option>
					`).join('')}
				</select>
			</div>
			<div class="wlsm-form-group wlsm-col-4" id="registration_dob_${id}">
				<label for="wlsm_date_of_birth_${id}" class="wlsm-font-bold">
					dateOfBirth :
				</label>
				<input type="date" name="dob_[${id}]" class="wlsm-form-control wlsm_date_of_birth datepicker" id="wlsm_date_of_birth_${id}" placeholder="">
			</div>
			</div>
			<div class="wlsm-row">

			<div class="wlsm-form-group wlsm-col-4" id="registration_student_type_${id}">
				<label for="wlsm_student_type_${id}" class="wlsm-font-bold">
					${StudentType}:
				</label>
				<select name="student_types_[${id}]" class="wlsm-form-control selectpicker" id="wlsm_student_type_${id}" data-live-search="true">
					${stypeList.map((stype) => `
						<option value="${stype.label}">${stype.label}</option>
					`).join('')}
				</select>
			</div>

			<div class = "wlsm-form-group wlsm-col-4">
			<label for = "wlsm_school_class_${id}" class = "wlsm-font-bold">
			<span class = "wlsm-important">*</span> ${Class}:
				</label>
				<select name = "class_id_[${id}]" class = "wlsm-form-control  wlsm_school_class_subject_${id} wlsm_school_class_activity_${id}" data-nonce = "${get_class_sections_nonce}" id = "wlsm_school_class_${id}">
				<option value="">Select Class</option>
					${classList.map((classItem) => `
						<option value = "${classItem.ID}">${classItem.label}</option>
					`).join('')}
				</select>
			</div>

			<div class="wlsm-form-group wlsm-col-4">
				<label for="wlsm_section_${id}" class="wlsm-font-bold">
					<span class="wlsm-important">*</span> ${Section}:
				</label>
				<select name="section_id_[${id}]" class="wlsm-form-control" id="wlsm_section_${id}">
					<option value="">Select Section</option>
				</select>
			</div>
			</div>
			<div class="wlsm-row">
			<div class="wlsm-form-group wlsm-col-4">
				<label for="wlsm_subjects_${id}" class="wlsm-font-bold">
					<span class="wlsm-important">*</span> ${Subjects}:
				</label><br>
				<select name="subjects_m[${id}][]" class="wlsm-form-control-select " id="wlsm_subjects_${id}" multiple>
					<option value="">${SelectSubjects}</option>
				</select>
			</div>

			<div class="wlsm-form-group wlsm-col-4">
				<label for="wlsm_activity_${id}" class="wlsm-font-bold">
					${Activity}:
				</label>
				<select name="activity_m[${id}][]" class="wlsm-form-control-select " id="wlsm_activity_${id}" multiple>
					<option value="">${SelectActivity}</option>
				</select>
			</div>

			<div class="wlsm-form-group wlsm-col-4" id="registration_blood_group_${id}">
				<label for="wlsm_blood_group_${id}" class="wlsm-font-bold">
					${BloodGroup}:
				</label>
				<select name="blood_group_[${id}]" class="wlsm-form-control selectpicker" id="wlsm_blood_group_${id}" data-live-search="true">
					<option value="">Select ${BloodGroup}</option>
					${blogdGroupList.map((group) => `
						<option value="${group}">${group}</option>
					`).join('')}
				</select>
			</div>
			</div>

			<div class="wlsm-form-section">
				<div class="wlsm-row">
					<div class="wlsm-col-12">
						<div class="wlsm-form-sub-heading wlsm-font-bold">
							${StudentLoginDetail}
						</div>
					</div>
				</div>

				<div class="wlsm-row wlsm-student-new-user">
					<div class="wlsm-form-group wlsm-col-4">
						<label for="wlsm_username" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> ${Username}:
						</label>
						<input type="text" name="username_[${id}]" class="wlsm-form-control" id="wlsm_username_${id}" placeholder="Enter username">
					</div>

					<div class="wlsm-form-group wlsm-col-4">
						<label for="wlsm_login_email" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> ${LoginEmail}:
						</label>
						<input type="email" name="login_email_[${id}]" class="wlsm-form-control" id="wlsm_login_email_${id}" placeholder="${EnterLoginEmail}">
					</div>

					<div class="wlsm-form-group wlsm-col-4">
						<label for="wlsm_login_password" class="wlsm-font-bold">
							<span class="wlsm-important">*</span> ${Password}:
						</label>
						<input type="password" name="password_[${id}]" class="wlsm-form-control" id="wlsm_login_password_${id}" placeholder=" ${EnterPassword} ">
					</div>
				</div>
			</div>

		</div>

		`);
	});

	$(document).on('change', '#wlsm_school', function() {
		var schoolId = this.value;
		var sessionId = $('#wlsm_session').val();
		var nonce = $(this).data('nonce');
		var classes = $('.wlsm_school_class');
		var sectionsExist = $(this).data('sections');


		// School registration form variables
		var dob           = $('#registration_dob');
		var religion      = $('#registration_religion');
		var caste         = $('#registration_caste');
		var blood_group   = $('#registration_blood_group');
		var phone         = $('#registration_phone');
		var city          = $('#registration_city');
		var state         = $('#registration_state');
		var country       = $('#registration_country');
		var transport     = $('#registration_transport');
		var parent_detail = $('#registration_parent_detail');
		var student_login = $('#registration_student_login');
		var parent_login  = $('#registration_parent_login');
		var id_number  	  = $('#registration_id');
		var survey	      = $('#registration_survey');
		var medium 		  = $('#registration_medium');

		var registration_school_details = $('#registration_school_details');
		var registration_birth_place 	= $('#registration_birth_place');
		var registration_mother_tongue 	= $('#registration_mother_tongue');
		var registration_dob_in_words 	= $('#registration_dob_in_words');

		$('div.wlsm-text-danger').remove();
		if(schoolId && nonce) {
			if(sectionsExist) {
				var sections = $('#wlsm_section');
				var firstOptionLabelSections = sections.find('option[value=""]').first().html();
				firstOptionLabelSections = '<option value="">' + firstOptionLabelSections + '</option>';
			}

			var firstOptionLabel = classes.find('option[value=""]').first().html();
			firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

			var data = 'action=wlsm-p-get-school-classes&nonce=' + nonce + '&school_id=' + schoolId;
			if(sessionId) {
				data += '&session_id=' + sessionId
			}
			if(classes.data('all-classes')) {
				data += '&all_classes=1';
			}
			$.ajax({
				data: data,
				url: wlsmajaxurl,
				type: 'POST',
				success: function(res) {
					var options = [firstOptionLabel];
					res.forEach(function(item) {
						if (item.class.label !== '') {
							var option = '<option value="' + item.class.ID + '">' + item.class.label + '</option>';
							options.push(option);
						}
					});
					dob           = (res[0].dob !== true) ? dob.hide() : dob.fadeIn();
					religion      = (res[0].religion !== true) ? religion.hide() : religion.fadeIn();
					caste         = (res[0].caste !== true) ? caste.hide() : caste.fadeIn();
					blood_group   = (res[0].blood_group !== true) ? blood_group.hide() : blood_group.fadeIn();
					phone         = (res[0].phone !== true) ? phone.hide() : phone.fadeIn();
					city          = (res[0].city !== true) ? city.hide() : city.fadeIn();
					state         = (res[0].state !== true) ? state.hide() : state.fadeIn();
					country       = (res[0].country !== true) ? country.hide() : country.fadeIn();
					transport     = (res[0].transport !== true) ? transport.hide() : transport.fadeIn();
					parent_detail = (res[0].parent_detail !== true) ? parent_detail.hide() : parent_detail.fadeIn();
					student_login = (res[0].student_login !== true) ? student_login.hide() : student_login.fadeIn();
					parent_login  = (res[0].parent_login !== true) ? parent_login.hide() : parent_login.fadeIn();
					id_number     = (res[0].id_number !== true) ? id_number.hide() : id_number.fadeIn();
					survey        = (res[0].survey    !== true) ? survey   .hide() : survey   .fadeIn();
					medium        = (res[0].medium    !== true) ? medium   .hide() : medium   .fadeIn();
					registration_school_details = (res[0].school_details !== true) ? registration_school_details.hide() : registration_school_details.fadeIn();
					registration_birth_place    = (res[0].birth_place !== true) ? registration_birth_place.hide() : registration_birth_place.fadeIn();
					registration_mother_tongue  = (res[0].mother_tongue !== true) ? registration_mother_tongue.hide() : registration_mother_tongue.fadeIn();
					registration_dob_in_words   = (res[0].dob_in_words !== true) ? registration_dob_in_words.hide() : registration_dob_in_words.fadeIn();

					classes.html(options);
					if(sectionsExist) {
						sections.html([firstOptionLabelSections]);
					}
				}
			});
		} else {
			classes.html([firstOptionLabel]);
			if(sectionsExist) {
				sections.html([firstOptionLabelSections]);
			}
		}
	});

	// Get class sections.
	$(document).on('change', '.wlsm_school_class', function() {
		var schoolId = $('#wlsm_school').val();
		var classId = this.value;
		var nonce = $(this).data('nonce');
		var sections = $('#wlsm_section');



		$('div.wlsm-text-danger').remove();
		if(schoolId && classId && nonce) {
			var firstOptionLabel = sections.find('option[value=""]').first().html();
			// firstOptionLabel = '<option value="">' + firstOptionLabel + '</option>';

			var data = 'action=wlsm-p-get-class-sections&nonce=' + nonce + '&school_id=' + schoolId + '&class_id=' + classId;
			if(sections.data('all-sections')) {
				data += '&all_sections=1';
			}
			$.ajax({
				data: data,
				url: wlsmajaxurl,
				type: 'POST',
				success: function(res) {
					var options = [firstOptionLabel];
					res.forEach(function(item) {
						var option = '<option value="' + item.ID + '">' + item.label + '</option>';
						options.push(option);
					});
					sections.html(options);
				}
			});
		} else {
			sections.html([firstOptionLabel]);
		}
	});

	$(document).on('change', '.wlsm_get_class_fees', function() {
		var schoolId = $('#wlsm_school').val();
		var student_type = $('#wlsm_student_type').val();
		var classId = this.value;
		var nonce = $(this).data('nonce');
		var feesElement = $('#wlsm_fees'); // Renamed to feesElement to avoid conflict
		var totalAmount = 0;

		$('div.wlsm-text-danger').remove();
		if (schoolId && classId && nonce) {
			var data = 'action=wlsm-p-get-class-fees&nonce=' + nonce + '&school_id=' + schoolId + '&class_id=' + classId + '&student_type=' + student_type;
			if (feesElement.data('all-fees')) {
				data += '&all_fees=1';
			}
			$.ajax({
				data: data,
				url: wlsmajaxurl,
				type: 'POST',
				success: function(res) {
					var months = res.session_months;
					var feesData = res.fees; // Renamed variable to avoid conflict

					// Check if feesData is an array
					if (!Array.isArray(feesData)) {
						console.error('feesData is not an array:', feesData);
						feesElement.html('<div class="wlsm-text-danger">Error: Invalid fees data received.</div>');
						return;
					}

					var table = '<table><thead><tr><th>Fee Type </th><th>Period</th><th>Amount</th> <th>Session Total</th></tr></thead><tbody>';
					var feesTotal = 0;
					var totalAmount = 0; // Initialize totalAmount

					console.log(feesData);
					feesData.forEach(function(item) {
						var count = 1;
						feesTotal += parseFloat(item.amount);
						if (item.period === 'monthly') {
							count = months;
						} else if (item.period === 'quarterly') {
							count = Math.ceil(months / 3);
						} else if (item.period === 'half-yearly') {
							count = Math.ceil(months / 6);
						} else if (item.period === 'one-time') {
							count = 1;
						} else if (item.period === 'annually') {
							count = Math.ceil(months / 12);
						} else if (item.period === 'quadrimester') {
							count = Math.ceil(months / 4);
						}
						var session_total = parseFloat(item.amount) * count;
						table += '<tr><td>' + item.label + '</td><td>' + item.period + '</td><td>' + item.amount + '</td><td>' + item.amount + ' x ' + count + ' = ' + session_total.toFixed(2) + '</td></tr>';
						totalAmount += parseFloat(session_total);
					});

					table += '<tr><td><b>Total</b></td><td></td><td>' + feesTotal.toFixed(2) + '</td><td><b>' + totalAmount.toFixed(2) + '</b></td></tr>';
					table += '</tbody></table>';

					feesElement.html(table); // Use feesElement here
				}
			});
		} else {
			feesElement.html(''); // Use feesElement here
		}
	});


	// Get class types.
	$(document).on('change', '.school_student_types', function() {
		var schoolId = $('#wlsm_school').val();
		var classId = this.value;
		var nonce = $(this).data('nonce');
		var types = $('#wlsm_student_type');

		$('div.wlsm-text-danger').remove();
		if(schoolId && classId && nonce) {

			var data = 'action=wlsm-p-get-student-types&nonce=' + nonce + '&school_id=' + schoolId + '&class_id=' + classId;

			if(types.data('all-types')) {
				data += '&all_types=1';
			}

			$.ajax({
				data: data,
				url: wlsmajaxurl,
				type: 'POST',
				success: function(res) {
					var options = [];
					res.forEach(function(item) {
						var option = '<option value="' + item.label + '">' + item.label + '</option>';
						options.push(option);
					});
					types.html(options);
				}
			});
		} else {
			types.html([]);
		}
	});


	$(document).ready(function() {

		var loaderContainer = $('<span/>', {
			'class': 'wlsm-loader wlsm-ml-2'
		});
		var loader = $('<img/>', {
			'src': wlsmadminurl + 'images/spinner.gif',
			'class': 'wlsm-loader-image wlsm-mb-1'
		});

		$(document).on('change', '#wlsm-select-all', function() {
			if($(this).is(':checked')) {
				$('.wlsm-select-single').prop('checked', true);
			} else {
				$('.wlsm-select-single').prop('checked', false);
			}
		});

		$('.SlectBox').SumoSelect();

		// Submit staff registration.
	var submitStaffRegistrationFormId = '#wlsm-submit-staff-registration-form';
	var submitStaffRegistrationForm = $(submitStaffRegistrationFormId);
	var submitStaffRegistrationBtn = $('#wlsm-submit-staff-registration-btn');

	function wlsmBeforeSubmit(button) {
		$('div.text-danger').remove();
		$(".is-invalid").removeClass("is-invalid");
		$('.wlsm .alert-dismissible').remove();
		button.prop('disabled', true);

		return true;
	}

	function wlsmDisplayFormErrors(response, formId) {
		if(response.data && $.isPlainObject(response.data)) {
			$(formId + ' :input').each(function() {
				var input = this;
				$(input).removeClass('wlsm-is-invalid');
				if(response.data[input.name]) {
					var errorSpan = '<div class="wlsm-text-danger wlsm-mt-1">' + response.data[input.name] + '</div>';
					$(input).addClass('wlsm-is-invalid');
					$(errorSpan).insertAfter(input);
				}
			});
		} else {
			var errorSpan = '<div class="wlsm-text-danger wlsm-mt-3">' + response.data + '<hr></div>';
			$(errorSpan).insertBefore(formId);
			toastr.error(response.data);
		}
	}
	function wlsmComplete(button) {
		button.prop('disabled', false);
		loaderContainer.remove();
	}

	submitStaffRegistrationForm.ajaxForm({
		beforeSubmit: function(arr, $form, options) {
			return wlsmBeforeSubmit(submitStaffRegistrationBtn);
		},
		success: function(response) {
			if(response.success) {
				toastr.success(response.data.message);
				submitStaffRegistrationForm.html('<div class="wlsm-alert wlsm-alert-success" role="alert">' + response.data.message + '</div>');
				if(response.data.hasOwnProperty('redirect_url') && response.data.redirect_url && ('#' !== response.data.redirect_url)) {
					setTimeout(function () {
						window.location.href = response.data.redirect_url;
					}, 1300);
				}
			} else {
				wlsmDisplayFormErrors(response, submitStaffRegistrationFormId);
			}
		},
		error: function(response) {
			wlsmDisplayFormError(response, submitStaffRegistrationFormId, submitStaffRegistrationBtn);
		},
		complete: function(event, xhr, settings) {
			wlsmComplete(submitStaffRegistrationBtn);
		}
	});

	// Student: BigBlueButton Join Meeting.
	$(document).on('click', '.wlsm-student-bbb-join-meeting', function(event) {
		event.preventDefault();
		console.log('Student BigBlueButton join meeting clicked');
		var $this = $(this);
		var originalText = $this.html();

		$this.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

		var data = {
			action: 'wlsm-student-bbb-join',
			meeting_id: $this.data('meeting-id'),
			password: $this.data('password'),
			recordable: $this.data('recordable'),
			nonce: $this.data('nonce')
		};

		$.post(wlsmajaxurl, data, function(response) {
			if (response.success) {
				window.open(response.data.url, '_blank');
			} else {
				toastr.error(response.data || 'Failed to generate meeting URL');
			}
		}).fail(function() {
			toastr.error('Network error occurred');
		}).always(function() {
			$this.html(originalText).prop('disabled', false);
		});
	});

	// Student: View academic report.
	$(document).on('click', '.wlsm-st-view-academic-report', function(event) {
		event.preventDefault();
		var element = $(this);
		var academicReportId = element.data('academic-report');
		var studentId = element.data('student');
		var title = element.data('message-title');
		var nonce = element.data('nonce');

		var data = {};
		data['academic_report_id'] = academicReportId;
		data['student_id'] = studentId;
		data['st-view-academic-report-' + academicReportId] = nonce;
		data['action'] = 'wlsm-p-st-view-academic-report';

		$.dialog({
			title: title,
			content: function() {
				var self = this;
				return $.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						if (res.success && res.data && res.data.html) {
							self.setContent(res.data.html);
						} else {
							self.setContent('');
						}
					}
				});
			},
			theme: 'bootstrap',
			useBootstrap: false,
			columnClass: 'large',
			backgroundDismiss: true
		});
	});

	// Student: View academic multi-group report.
	$(document).on('click', '.wlsm-st-view-academic-multi-group-report', function(event) {
		event.preventDefault();
		var element = $(this);
		var academicReportId = element.data('academic-report');
		var studentId = element.data('student');
		var title = element.data('message-title');
		var nonce = element.data('nonce');

		var data = {};
		data['academic_report_id'] = academicReportId;
		data['student_id'] = studentId;
		data['st-view-academic-multi-group-report-' + academicReportId] = nonce;
		data['action'] = 'wlsm-p-st-view-academic-multi-group-report';

		$.dialog({
			title: title,
			content: function() {
				var self = this;
				return $.ajax({
					data: data,
					url: wlsmajaxurl,
					type: 'POST',
					success: function(res) {
						if (res.success && res.data && res.data.html) {
							self.setContent(res.data.html);
						} else {
							self.setContent('');
						}
					}
				});
			},
			theme: 'bootstrap',
			useBootstrap: false,
			columnClass: 'large',
			backgroundDismiss: true
		});
	});

	}); // End of document.ready

})(jQuery);
