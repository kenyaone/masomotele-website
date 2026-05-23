<?php
defined('ABSPATH') || die();
?>

<div class="wlsm-grid">
    <?php if (empty($lessons)): ?>
        <p><?php esc_html_e('No lessons found.', 'school-management'); ?></p>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($lessons as $lesson): ?>
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h3>
                        <?php if (!empty($lesson->url)): ?>
                            <a href="<?php echo esc_url($lesson->url); ?>"><?php echo esc_html($lesson->title); ?></a>
                        <?php else: ?>
                            <?php echo esc_html($lesson->title); ?>
                        <?php endif; ?>
                    </h3>

                    <p><strong><?php esc_html_e('Subject:', 'school-management'); ?></strong> <?php echo esc_html($lesson->subject); ?></p>
                    <p><strong><?php esc_html_e('Chapter:', 'school-management'); ?></strong> <?php echo esc_html($lesson->chapter); ?></p>

                    <details>
                        <summary><strong><?php esc_html_e('Description', 'school-management'); ?></strong> (<?php esc_html_e('Click to expand', 'school-management'); ?>)</summary>
                        <div style="padding: 10px 0;">
                            <?php echo wp_kses_post($lesson->description); ?>
                        </div>
                    </details>

                    <?php if ($lesson->link_to == 'attachment' && !empty($lesson->attachment)): ?>
                        <p style="margin-top: 10px;">
                            <a href="<?php echo esc_url(wp_get_attachment_url($lesson->attachment)); ?>" target="_blank">
                                <?php esc_html_e('View Attachment', 'school-management'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php
        // Simple pagination
        if ($lesson_total > $lesson_per_page) {
            $total_pages = ceil($lesson_total / $lesson_per_page);
            echo '<div style="margin-top: 20px; text-align: center;">';
            for ($i = 1; $i <= $total_pages; $i++) {
                if ($i == $lesson_page) {
                    echo '<span style="padding: 5px 10px; margin: 0 5px; background: #f0f0f0; border: 1px solid #ccc;">' . $i . '</span>';
                } else {
                    echo '<a href="' . add_query_arg('lesson_page', $i) . '" style="padding: 5px 10px; margin: 0 5px; border: 1px solid #ccc; text-decoration: none;">' . $i . '</a>';
                }
            }
            echo '</div>';
        }
        ?>
    <?php endif; ?>
</div>
