/**
 * MTTI Learner Portal JavaScript — v5.0.0
 * Features: dark mode, animated stats, notification centre, AI assistant, leaderboard goal
 */
(function($) {
    // === ASTRA THEME ISOLATION ===
    // Add body class immediately so CSS overrides work (fallback for browsers without :has())
    if (document.getElementById('mtti-portal')) {
        document.body.classList.add('mtti-portal-active');
        // Also hide the WP admin bar gap if present
        var adminBar = document.getElementById('wpadminbar');
        if (adminBar) {
            document.documentElement.style.setProperty('margin-top', '0', 'important');
        }
    }
    'use strict';

    // ─── DARK MODE ────────────────────────────────────────
    var DARK_KEY = 'mtti_dark_mode';
    function applyTheme(dark) {
        var $w = $('.mtti-portal-wrapper');
        if (dark) {
            $w.attr('data-theme', 'dark');
            $('.mtti-dark-toggle').text('☀️').attr('title', 'Switch to light mode');
        } else {
            $w.removeAttr('data-theme');
            $('.mtti-dark-toggle').text('🌙').attr('title', 'Switch to dark mode');
        }
    }
    // Init: read saved or system preference
    var savedDark = localStorage.getItem(DARK_KEY);
    var isDark = savedDark !== null
        ? (savedDark === '1')
        : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);
    applyTheme(isDark);

    $(document).on('click', '.mtti-dark-toggle', function() {
        isDark = !isDark;
        localStorage.setItem(DARK_KEY, isDark ? '1' : '0');
        applyTheme(isDark);
    });

    // ─── ANIMATED STAT COUNTERS ───────────────────────────
    function animateCounter($el) {
        var raw = $el.text().replace(/[^0-9.]/g, '');
        var target = parseFloat(raw);
        if (isNaN(target) || target === 0) return;
        var prefix = $el.text().replace(/[\d,.]+/, '').split(raw)[0] || '';
        var suffix = $el.text().split(raw).pop() || '';
        var isFloat = raw.indexOf('.') !== -1;
        var duration = 800;
        var start = null;
        function step(ts) {
            if (!start) start = ts;
            var progress = Math.min((ts - start) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
            var current = target * eased;
            var display = isFloat ? current.toFixed(1) : Math.floor(current).toLocaleString();
            $el.text(prefix + display + suffix);
            if (progress < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    }

    function initCounters() {
        $('.mtti-stat-value').each(function() {
            animateCounter($(this));
        });
    }

    // Intersection observer to trigger counters when visible
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    initCounters();
                    observer.disconnect();
                }
            });
        }, { threshold: 0.2 });
        var statsGrid = document.querySelector('.mtti-stats-grid');
        if (statsGrid) observer.observe(statsGrid);
    } else {
        setTimeout(initCounters, 300);
    }

    // ─── PROGRESS RING ────────────────────────────────────
    function initProgressRings() {
        $('.mtti-ring-fill').each(function() {
            var $ring = $(this);
            var pct = parseFloat($ring.data('pct') || 0);
            var r = parseFloat($ring.attr('r') || 40);
            var circumference = 2 * Math.PI * r;
            var offset = circumference - (pct / 100) * circumference;
            $ring.css({
                'stroke-dasharray': circumference,
                'stroke-dashoffset': circumference
            });
            setTimeout(function() {
                $ring.css('stroke-dashoffset', offset);
            }, 200);
        });
    }
    initProgressRings();

    // ─── NOTIFICATION CENTRE ──────────────────────────────
    var $notifWrap = $('.mtti-notif-wrap');
    var $notifBtn = $notifWrap.find('.mtti-notif-btn');
    var $notifDropdown = $notifWrap.find('.mtti-notif-dropdown');

    // Toggle dropdown
    $notifBtn.on('click', function(e) {
        e.stopPropagation();
        $notifDropdown.toggleClass('open');
        if ($notifDropdown.hasClass('open')) {
            fetchNotifications();
        }
    });
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.mtti-notif-wrap').length) {
            $notifDropdown.removeClass('open');
        }
    });

    function fetchNotifications() {
        $.ajax({
            url: mttiPortal.ajaxUrl,
            type: 'POST',
            data: { action: 'mtti_get_notifications', nonce: mttiPortal.nonce },
            success: function(res) {
                if (!res.success) return;
                var items = res.data.notifications || [];
                var unread = res.data.unread_count || 0;

                // Update badge
                var $badge = $notifWrap.find('.mtti-notif-badge');
                if (unread > 0) {
                    if (!$badge.length) {
                        $notifBtn.append('<span class="mtti-notif-badge">' + (unread > 9 ? '9+' : unread) + '</span>');
                    } else {
                        $badge.text(unread > 9 ? '9+' : unread).show();
                    }
                } else {
                    $badge.hide();
                }

                // Render list
                var $list = $notifDropdown.find('.mtti-notif-scroll');
                $list.empty();
                if (items.length === 0) {
                    $list.html('<div class="mtti-notif-empty">🎉 You\'re all caught up!</div>');
                    return;
                }
                items.forEach(function(n) {
                    var cls = n.is_read == '1' ? '' : ' unread';
                    var dotCls = 'notif-' + (n.type || 'info');
                    var time = timeAgo(n.created_at);
                    $list.append(
                        '<div class="mtti-notif-item' + cls + '" data-id="' + n.notification_id + '">' +
                        '<div class="mtti-notif-dot ' + dotCls + '"></div>' +
                        '<div class="mtti-notif-text"><strong>' + escHtml(n.title) + '</strong>' +
                        '<span>' + escHtml(n.message) + '</span></div>' +
                        '<div class="mtti-notif-time">' + time + '</div>' +
                        '</div>'
                    );
                });
            }
        });
    }

    // Mark all read
    $(document).on('click', '.mtti-notif-mark-all', function() {
        $.post(mttiPortal.ajaxUrl, { action: 'mtti_mark_notifications_read', nonce: mttiPortal.nonce }, function() {
            $notifDropdown.find('.mtti-notif-item').removeClass('unread');
            $notifWrap.find('.mtti-notif-badge').hide();
        });
    });

    // Poll for new notifications every 90 seconds
    if ($notifBtn.length) {
        fetchNotifications(); // initial load
        setInterval(function() {
            $.ajax({
                url: mttiPortal.ajaxUrl,
                type: 'POST',
                data: { action: 'mtti_get_notifications', nonce: mttiPortal.nonce },
                success: function(res) {
                    if (res.success) {
                        var unread = res.data.unread_count || 0;
                        var $badge = $notifWrap.find('.mtti-notif-badge');
                        if (unread > 0) {
                            if (!$badge.length) {
                                $notifBtn.append('<span class="mtti-notif-badge">' + (unread > 9 ? '9+' : unread) + '</span>');
                            } else {
                                $badge.text(unread > 9 ? '9+' : unread).show();
                            }
                        } else {
                            $badge.hide();
                        }
                    }
                }
            });
        }, 90000);
    }

    // ─── AI ASSISTANT ─────────────────────────────────────
    var aiMsgCount = 0;
    var AI_LIMIT = 20;

    function scrollAiToBottom() {
        var $msgs = $('.mtti-ai-messages');
        if ($msgs.length) $msgs.scrollTop($msgs[0].scrollHeight);
    }

    function appendBubble(text, role) {
        var cls = role === 'user' ? 'user' : 'bot';
        var sender = role === 'user' ? 'You' : 'MTTI AI Tutor';
        var $bubble = $(
            '<div class="mtti-chat-bubble ' + cls + '">' +
            '<div class="mtti-chat-sender">' + sender + '</div>' +
            '<div class="mtti-chat-text">' + text + '</div>' +
            '</div>'
        );
        $('.mtti-ai-messages').append($bubble);
        scrollAiToBottom();
        return $bubble;
    }

    function showTyping() {
        var $t = $('<div class="mtti-chat-typing"><div class="mtti-typing-dot"></div><div class="mtti-typing-dot"></div><div class="mtti-typing-dot"></div></div>');
        $('.mtti-ai-messages').append($t);
        scrollAiToBottom();
        return $t;
    }

    function sendAiMessage(message) {
        if (!message.trim()) return;
        if (aiMsgCount >= AI_LIMIT) {
            appendBubble('You\'ve reached today\'s message limit (20). Come back tomorrow! 😊', 'bot');
            return;
        }
        aiMsgCount++;
        appendBubble(escHtml(message), 'user');
        var $send = $('.mtti-ai-send');
        $send.prop('disabled', true);
        var $typing = showTyping();

        $.ajax({
            url: mttiPortal.ajaxUrl,
            type: 'POST',
            data: { action: 'mtti_ai_chat', nonce: mttiPortal.nonce, message: message },
            timeout: 30000,
            success: function(res) {
                $typing.remove();
                if (res.success) {
                    appendBubble(res.data.reply.replace(/\n/g, '<br>'), 'bot');
                } else {
                    appendBubble('Sorry, I couldn\'t get a response. Try again in a moment.', 'bot');
                }
            },
            error: function() {
                $typing.remove();
                appendBubble('Network error. Please check your connection and try again.', 'bot');
            },
            complete: function() {
                $send.prop('disabled', false);
                var remaining = AI_LIMIT - aiMsgCount;
                $('.mtti-ai-limit').text(remaining + ' messages remaining today');
                scrollAiToBottom();
            }
        });
    }

    // Send on button click
    $(document).on('click', '.mtti-ai-send', function() {
        var $input = $('.mtti-ai-input');
        sendAiMessage($input.val());
        $input.val('').focus();
    });

    // Send on Enter (Shift+Enter for newline)
    $(document).on('keydown', '.mtti-ai-input', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            var $input = $(this);
            sendAiMessage($input.val());
            $input.val('');
        }
    });

    // Quick prompts
    $(document).on('click', '.mtti-ai-quick', function() {
        var prompt = $(this).data('prompt');
        sendAiMessage(prompt);
    });

    // ─── CALENDAR ─────────────────────────────────────────
    var calYear, calMonth;
    function initCalendar() {
        var now = new Date();
        calYear = now.getFullYear();
        calMonth = now.getMonth();
        renderCalendar();
    }

    function renderCalendar() {
        var $cal = $('.mtti-cal-grid-container');
        if (!$cal.length) return;
        var events = window.mttiCalEvents || [];
        var today = new Date();
        var firstDay = new Date(calYear, calMonth, 1).getDay();
        var daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();
        var daysInPrev = new Date(calYear, calMonth, 0).getDate();
        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

        $('.mtti-cal-nav h3').text(monthNames[calMonth] + ' ' + calYear);

        var html = '';
        // Day headers
        ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].forEach(function(d) {
            html += '<div class="mtti-cal-header">' + d + '</div>';
        });

        // Adjust for Mon start (0=Sun → make Mon first)
        var startOffset = (firstDay === 0) ? 6 : firstDay - 1;

        for (var i = 0; i < startOffset; i++) {
            var pDay = daysInPrev - startOffset + i + 1;
            html += '<div class="mtti-cal-day other-month">' + pDay + '</div>';
        }

        for (var d = 1; d <= daysInMonth; d++) {
            var isToday = (today.getFullYear() === calYear && today.getMonth() === calMonth && today.getDate() === d);
            var dateStr = calYear + '-' + pad2(calMonth + 1) + '-' + pad2(d);
            var hasEv = events.some(function(e) { return e.date === dateStr; });
            var cls = 'mtti-cal-day' + (isToday ? ' today' : '') + (hasEv ? ' has-event' : '');
            html += '<div class="' + cls + '" data-date="' + dateStr + '">' + d + '</div>';
        }

        var totalCells = startOffset + daysInMonth;
        var remaining = (7 - (totalCells % 7)) % 7;
        for (var n = 1; n <= remaining; n++) {
            html += '<div class="mtti-cal-day other-month">' + n + '</div>';
        }

        $cal.html(html);
        renderEventsList(null); // show this month's events
    }

    function renderEventsList(dateFilter) {
        var events = window.mttiCalEvents || [];
        var $list = $('.mtti-events-list');
        if (!$list.length) return;

        var filtered = events.filter(function(e) {
            if (dateFilter) return e.date === dateFilter;
            // Show events for current month
            var d = new Date(e.date);
            return d.getFullYear() === calYear && d.getMonth() === calMonth;
        });

        if (filtered.length === 0) {
            $list.html('<p style="color:var(--text-3);font-size:13px;text-align:center;padding:20px 0;">' +
                (dateFilter ? 'No events on this day.' : 'No events this month.') + '</p>');
            return;
        }

        filtered.sort(function(a,b) { return a.date.localeCompare(b.date); });
        var html = '';
        filtered.forEach(function(e) {
            html += '<div class="mtti-event-item">' +
                '<div class="mtti-event-dot event-' + escHtml(e.type) + '"></div>' +
                '<div class="mtti-event-text"><strong>' + escHtml(e.title) + '</strong>' +
                '<span>' + escHtml(e.date) + (e.time ? ' · ' + escHtml(e.time) : '') + '</span></div>' +
                '</div>';
        });
        $list.html(html);
    }

    $(document).on('click', '.mtti-cal-day[data-date]', function() {
        var date = $(this).data('date');
        renderEventsList(date);
        $('.mtti-cal-day').removeClass('selected');
        $(this).addClass('selected');
    });
    $(document).on('click', '.mtti-cal-nav-btn[data-dir]', function() {
        var dir = $(this).data('dir');
        calMonth += (dir === 'next' ? 1 : -1);
        if (calMonth > 11) { calMonth = 0; calYear++; }
        if (calMonth < 0)  { calMonth = 11; calYear--; }
        renderCalendar();
    });

    if ($('.mtti-cal-grid-container').length) initCalendar();

    // ─── GOAL WIDGET ──────────────────────────────────────
    $(document).on('click', '.mtti-goal-btn', function() {
        var $btn = $(this);
        var $input = $('.mtti-goal-input');
        var goal = $input.val().trim();
        if (!goal) return;

        $.post(mttiPortal.ajaxUrl, {
            action: 'mtti_save_goal',
            nonce: mttiPortal.nonce,
            goal: goal
        }, function(res) {
            if (res.success) location.reload();
        });
    });

    $(document).on('click', '.mtti-goal-complete', function() {
        $.post(mttiPortal.ajaxUrl, {
            action: 'mtti_complete_goal',
            nonce: mttiPortal.nonce
        }, function(res) {
            if (res.success) location.reload();
        });
    });

    // ─── ASSIGNMENT MODAL ─────────────────────────────────
    $(document).on('click', '.mtti-submit-btn', function(e) {
        e.preventDefault();
        $('#modal-assignment-id').val($(this).data('id'));
        $('#mtti-submit-modal').css('display', 'flex');
    });
    $(document).on('click', '.mtti-modal-close', function(e) {
        e.preventDefault();
        $('.mtti-modal').css('display', 'none');
    });
    $(document).on('click', '.mtti-modal', function(e) {
        if (e.target === this) $(this).css('display', 'none');
    });
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') $('.mtti-modal').css('display', 'none');
    });

    $(document).on('submit', '#mtti-submit-form', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var fd = new FormData(this);
        $btn.prop('disabled', true).text('Submitting...');
        $.ajax({
            url: mttiPortal.ajaxUrl, type: 'POST',
            data: fd, processData: false, contentType: false,
            success: function(r) {
                if (r.success) { alert(r.data.message); location.reload(); }
                else alert(r.data.message || 'An error occurred');
            },
            error: function() { alert('Network error. Please try again.'); },
            complete: function() { $btn.prop('disabled', false).text('📤 Submit Assignment'); }
        });
    });

    // ─── FADE-IN ANIMATION ────────────────────────────────
    $('.mtti-dashboard-card, .mtti-stat-card, .mtti-unit-item, .mtti-lb-row').each(function(i) {
        var $el = $(this);
        $el.css({ opacity: 0, transform: 'translateY(10px)' });
        setTimeout(function() {
            $el.css({ transition: 'opacity 0.3s ease, transform 0.3s ease', opacity: 1, transform: 'translateY(0)' });
        }, i * 40);
    });

    // ─── HELPERS ──────────────────────────────────────────
    function escHtml(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
    function pad2(n) { return n < 10 ? '0' + n : '' + n; }
    function timeAgo(dateStr) {
        var d = new Date(dateStr.replace(' ', 'T'));
        var diff = (Date.now() - d.getTime()) / 1000;
        if (diff < 60) return 'just now';
        if (diff < 3600) return Math.floor(diff/60) + 'm ago';
        if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
        if (diff < 604800) return Math.floor(diff/86400) + 'd ago';
        return d.toLocaleDateString('en-KE', { day: 'numeric', month: 'short' });
    }

    // Auto-hide alerts
    setTimeout(function() { $('.mtti-alert').fadeOut(); }, 5000);

    // ─── STUDY CHAT — REPLY TOGGLE ────────────────────────
    $(document).on('click', '.mtti-reply-toggle', function() {
        var id = $(this).data('id');
        var $replies = $('#replies-' + id);
        $replies.slideToggle(180);
    });

    // ─── QUIZ SYSTEM ──────────────────────────────────────
    $(document).on('click', '.mtti-start-quiz', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var quizId = $btn.data('quiz-id');
        var quizTitle = $btn.data('quiz-title');

        $btn.prop('disabled', true).text('Starting...');

        $.ajax({
            url: mttiPortal.ajaxUrl,
            type: 'POST',
            data: {
                action: 'mtti_start_quiz_attempt',
                nonce: mttiPortal.quizNonce,
                quiz_id: quizId
            },
            success: function(response) {
                if (response.success) {
                    mttiShowQuizUI(response.data);
                } else {
                    alert('Error starting quiz: ' + (response.data || 'Unknown error'));
                    $btn.prop('disabled', false).text('Start Quiz');
                }
            },
            error: function() {
                alert('Error starting quiz. Please try again.');
                $btn.prop('disabled', false).text('Start Quiz');
            }
        });
    });

    function mttiShowQuizUI(data) {
        var quiz = data.quiz;
        var questions = data.questions;
        var attemptToken = data.attempt_token;
        var timeLimit = data.time_limit ? data.time_limit * 60 : null;

        var $modal = $('<div class="mtti-quiz-modal" style="display:none;"></div>');
        var html = '<div class="mtti-quiz-container" style="max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">';

        html += '<div class="mtti-quiz-header" style="background: linear-gradient(135deg, #0d9e3a 0%, #0a7a2d 100%); color: white; padding: 20px;">';
        html += '<h2 style="margin: 0 0 10px 0;">' + escapeHtml(quiz.title) + '</h2>';
        if (quiz.description) {
            html += '<p style="margin: 0; font-size: 14px; opacity: 0.9;">' + escapeHtml(quiz.description) + '</p>';
        }
        html += '<div style="font-size: 12px; margin-top: 10px;">';
        html += 'Questions: <strong>' + questions.length + '</strong> | ';
        html += 'Pass Mark: <strong>' + quiz.pass_mark + '%</strong>';
        if (timeLimit) {
            html += ' | Time: <strong id="mtti-quiz-timer">' + Math.floor(timeLimit / 60) + ':00</strong>';
        }
        html += '</div>';
        html += '</div>';

        html += '<div class="mtti-quiz-body" style="padding: 30px; overflow-y: auto; max-height: 60vh;">';
        html += '<form id="mtti-quiz-form">';

        // Render each question
        $.each(questions, function(i, q) {
            var qNum = i + 1;
            html += '<div class="mtti-question" style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 1px solid #eee;">';
            html += '<h4 style="color: #333; margin: 0 0 15px 0; font-size: 16px;">';
            html += '<span style="background: #0d9e3a; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-right: 10px;">' + qNum + '</span>';
            html += escapeHtml(q.question_text);
            html += '</h4>';

            if (q.question_type === 'mcq') {
                var options = JSON.parse(q.options);
                $.each(options, function(idx, opt) {
                    html += '<label style="display: block; margin: 10px 0; cursor: pointer; padding: 10px; border-radius: 4px; border: 1px solid #ddd; transition: all 0.2s;">';
                    html += '<input type="radio" name="q' + q.question_id + '" value="' + idx + '" style="margin-right: 10px;"> ';
                    html += escapeHtml(opt);
                    html += '</label>';
                });
            } else if (q.question_type === 'true_false') {
                html += '<label style="display: block; margin: 10px 0; cursor: pointer; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">';
                html += '<input type="radio" name="q' + q.question_id + '" value="true" style="margin-right: 10px;"> True';
                html += '</label>';
                html += '<label style="display: block; margin: 10px 0; cursor: pointer; padding: 10px; border-radius: 4px; border: 1px solid #ddd;">';
                html += '<input type="radio" name="q' + q.question_id + '" value="false" style="margin-right: 10px;"> False';
                html += '</label>';
            } else if (q.question_type === 'fill_blank') {
                html += '<input type="text" name="q' + q.question_id + '" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box;" placeholder="Your answer...">';
            }

            html += '</div>';
        });

        html += '</form>';
        html += '</div>';

        html += '<div class="mtti-quiz-footer" style="background: #f5f5f5; padding: 20px; text-align: right; border-top: 1px solid #ddd;">';
        html += '<button type="button" class="button button-secondary" id="mtti-quiz-cancel">Cancel</button> ';
        html += '<button type="button" class="button button-primary" id="mtti-quiz-submit">Submit Quiz</button>';
        html += '</div>';

        html += '</div>';
        $modal.html(html);

        // Show modal as overlay
        var overlayHtml = '<div id="mtti-quiz-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; overflow-y: auto; padding: 20px;"></div>';
        $('body').append(overlayHtml);
        $('#mtti-quiz-overlay').append($modal).fadeIn();
        $modal.fadeIn();

        // Timer
        if (timeLimit) {
            var timeRemaining = timeLimit;
            setInterval(function() {
                timeRemaining--;
                var mins = Math.floor(timeRemaining / 60);
                var secs = timeRemaining % 60;
                $('#mtti-quiz-timer').text(mins + ':' + (secs < 10 ? '0' : '') + secs);
                if (timeRemaining <= 0) {
                    $('#mtti-quiz-submit').click();
                }
            }, 1000);
        }

        // Cancel button
        $('#mtti-quiz-cancel').on('click', function() {
            if (confirm('Cancel this quiz attempt?')) {
                $('#mtti-quiz-overlay').fadeOut(function() { $(this).remove(); });
                $modal.fadeOut();
            }
        });

        // Submit button
        $('#mtti-quiz-submit').on('click', function() {
            var answers = {};
            $('#mtti-quiz-form').find('input[type="radio"]:checked, input[type="text"]').each(function() {
                var name = $(this).attr('name');
                var qId = parseInt(name.substring(1));
                answers[qId] = $(this).val();
            });

            $.ajax({
                url: mttiPortal.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mtti_submit_quiz_attempt',
                    nonce: mttiPortal.quizNonce,
                    quiz_id: quiz.quiz_id,
                    answers: JSON.stringify(answers),
                    attempt_token: attemptToken
                },
                success: function(response) {
                    if (response.success) {
                        mttiShowQuizResults(response.data, questions);
                    } else {
                        alert('Error submitting quiz: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Error submitting quiz. Please try again.');
                }
            });
        });
    }

    function mttiShowQuizResults(results, questions) {
        var html = '<div class="mtti-quiz-results" style="max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden;">';

        var resultColor = results.passed ? '#0d9e3a' : '#d92e25';
        var resultText = results.passed ? 'PASSED' : 'FAILED';

        html += '<div class="mtti-results-header" style="background: linear-gradient(135deg, ' + resultColor + ' 0%, ' + (results.passed ? '#0a7a2d' : '#a31f15') + ' 100%); color: white; padding: 40px; text-align: center;">';
        html += '<h2 style="margin: 0 0 10px 0; font-size: 28px;">' + resultText + '</h2>';
        html += '<div style="font-size: 48px; font-weight: bold; margin: 20px 0;">' + results.percentage.toFixed(1) + '%</div>';
        html += '<div style="font-size: 18px;">Score: <strong>' + results.score.toFixed(2) + '/' + results.total.toFixed(2) + '</strong></div>';
        html += '<div style="margin-top: 10px; font-size: 14px;">Pass Mark: ' + results.pass_mark + '%</div>';
        html += '</div>';

        html += '<div class="mtti-results-body" style="padding: 30px; max-height: 60vh; overflow-y: auto;">';
        html += '<h3>Review Your Answers</h3>';

        var questionMap = {};
        $.each(questions, function(i, q) {
            questionMap[q.question_id] = q;
        });

        $.each(results.question_results, function(qId, result) {
            var q = questionMap[qId];
            if (!q) return;

            var bgColor = result.correct ? '#e8f5e9' : '#ffebee';
            var borderColor = result.correct ? '#4caf50' : '#f44336';
            var statusText = result.correct ? '✓ Correct' : '✗ Incorrect';
            var statusColor = result.correct ? '#0d9e3a' : '#d92e25';

            html += '<div style="margin: 20px 0; padding: 15px; background: ' + bgColor + '; border-left: 4px solid ' + borderColor + '; border-radius: 4px;">';
            html += '<strong>' + escapeHtml(q.question_text) + '</strong><br>';
            html += '<span style="color: ' + statusColor + '; font-weight: bold; font-size: 14px;">' + statusText + '</span>';
            if (result.explanation) {
                html += '<div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.1); font-size: 13px; color: #666;"><strong>Explanation:</strong> ' + escapeHtml(result.explanation) + '</div>';
            }
            html += '</div>';
        });

        html += '</div>';

        html += '<div class="mtti-results-footer" style="background: #f5f5f5; padding: 20px; text-align: center; border-top: 1px solid #ddd;">';
        html += '<button type="button" class="button button-primary" onclick="location.reload();">Back to Quizzes</button>';
        html += '</div>';

        html += '</div>';

        $('#mtti-quiz-overlay').find('.mtti-quiz-container').fadeOut(function() {
            $(this).replaceWith(html);
            $('html, body').animate({ scrollTop: $('#mtti-quiz-overlay').offset().top - 50 }, 300);
        });
    }

    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

})(jQuery);
