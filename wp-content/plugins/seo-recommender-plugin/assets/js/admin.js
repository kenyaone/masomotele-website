jQuery(document).ready(function($) {
    
    // Analyze button click
    $('#analyze-btn').on('click', function() {
        var postId = $('#post-select').val();
        
        if (!postId) {
            alert('Please select a post to analyze');
            return;
        }
        
        // Show loading
        $('#loading').removeClass('hidden');
        $('#recommendations-container').addClass('hidden');
        
        // AJAX request
        $.ajax({
            url: seoRecommender.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_recommendations',
                post_id: postId,
                nonce: seoRecommender.nonce
            },
            success: function(response) {
                if (response.success) {
                    displayRecommendations(response.data);
                } else {
                    alert('Error analyzing post');
                }
                $('#loading').addClass('hidden');
            },
            error: function() {
                alert('AJAX error');
                $('#loading').addClass('hidden');
            }
        });
    });
    
    // Display recommendations
    function displayRecommendations(recommendations) {
        var html = '<div class="recommendations">';
        
        if (recommendations.length === 0) {
            html += '<p class="success">✓ Great! Your content is well optimized.</p>';
        } else {
            // Count by severity
            var critical = recommendations.filter(function(r) { return r.severity === 'critical'; }).length;
            var warning = recommendations.filter(function(r) { return r.severity === 'warning'; }).length;
            var info = recommendations.filter(function(r) { return r.severity === 'info'; }).length;
            
            html += '<div style="margin-bottom: 20px;">';
            if (critical > 0) html += '<span style="color: #dc3545; font-weight: bold; margin-right: 15px;">❌ ' + critical + ' Critical Issues</span>';
            if (warning > 0) html += '<span style="color: #ff9800; font-weight: bold; margin-right: 15px;">⚠️ ' + warning + ' Warnings</span>';
            if (info > 0) html += '<span style="color: #17a2b8; font-weight: bold;">ℹ️ ' + info + ' Tips</span>';
            html += '</div>';
            
            html += '<div class="issues-list">';
            recommendations.forEach(function(rec) {
                var icon = rec.severity === 'critical' ? '❌' : 
                           rec.severity === 'warning' ? '⚠️' : 'ℹ️';
                
                html += '<div class="recommendation ' + rec.severity + '">';
                html += '<p><strong>' + icon + ' ' + rec.title + '</strong></p>';
                html += '<p>' + rec.message + '</p>';
                html += '<p class="suggestion"><strong>Suggestion:</strong> ' + rec.suggestion + '</p>';
                html += '</div>';
            });
            html += '</div>';
        }
        
        html += '</div>';
        $('#recommendations-list').html(html);
        $('#recommendations-container').removeClass('hidden');
    }
    
    // Real-time analysis on post edit
    if ($('#post').length > 0) {
        var postId = $('#post_ID').val();
        
        // Auto-update recommendations
        setInterval(function() {
            $.ajax({
                url: seoRecommender.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'analyze_post',
                    post_id: postId,
                    nonce: seoRecommender.nonce
                }
            });
        }, 5000); // Check every 5 seconds
    }
});
