<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MTTI_MIS_Admin_Income {

    private $plugin_name;
    private $version;
    private $table;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version     = $version;
        global $wpdb;
        $this->table = $wpdb->prefix . 'mtti_income';
    }

    /* ─── Create table ─────────────────────────────────────────────── */
    public static function create_table() {
        global $wpdb;
        $table   = $wpdb->prefix . 'mtti_income';
        $charset = $wpdb->get_charset_collate();
        if ( $wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table ) {
            $sql = "CREATE TABLE $table (
                income_id      BIGINT NOT NULL AUTO_INCREMENT,
                income_date    DATE NOT NULL,
                category       VARCHAR(100) NOT NULL,
                description    VARCHAR(300) NOT NULL,
                amount         DECIMAL(10,2) NOT NULL,
                received_from  VARCHAR(200) DEFAULT NULL,
                payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash',
                reference      VARCHAR(100) DEFAULT NULL,
                recorded_by    BIGINT UNSIGNED DEFAULT NULL,
                notes          TEXT DEFAULT NULL,
                created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (income_id),
                KEY income_date (income_date),
                KEY category (category)
            ) $charset;";
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        }
    }

    /* ─── Handle POST ──────────────────────────────────────────────── */
    private function handle_actions() {
        global $wpdb;
        if ( ! isset( $_POST['mtti_income_nonce'] ) ||
             ! wp_verify_nonce( $_POST['mtti_income_nonce'], 'mtti_income_action' ) ) return;

        $action = sanitize_text_field( $_POST['action_type'] ?? '' );

        if ( $action === 'add' || $action === 'edit' ) {
            $data = [
                'income_date'    => sanitize_text_field( $_POST['income_date'] ),
                'category'       => sanitize_text_field( $_POST['category'] ),
                'description'    => sanitize_text_field( $_POST['description'] ),
                'amount'         => floatval( $_POST['amount'] ),
                'received_from'  => sanitize_text_field( $_POST['received_from'] ),
                'payment_method' => sanitize_text_field( $_POST['payment_method'] ),
                'reference'      => sanitize_text_field( $_POST['reference'] ),
                'notes'          => sanitize_textarea_field( $_POST['notes'] ),
                'recorded_by'    => get_current_user_id(),
            ];
            if ( $action === 'add' ) {
                $wpdb->insert( $this->table, $data );
                echo '<div class="notice notice-success"><p>✅ Income recorded.</p></div>';
            } else {
                $wpdb->update( $this->table, $data, [ 'income_id' => intval($_POST['income_id']) ] );
                echo '<div class="notice notice-success"><p>✅ Income updated.</p></div>';
            }
        }
        if ( $action === 'delete' ) {
            $wpdb->delete( $this->table, [ 'income_id' => intval($_POST['income_id']) ] );
            echo '<div class="notice notice-warning"><p>🗑️ Income entry deleted.</p></div>';
        }
    }

    /* ─── Main display ─────────────────────────────────────────────── */
    public function display() {
        self::create_table();
        $this->handle_actions();
        global $wpdb;

        $month = sanitize_text_field( $_GET['month'] ?? date('Y-m') );
        $cat   = sanitize_text_field( $_GET['cat'] ?? '' );

        // --- Manual income filters ---
        $where = $wpdb->prepare('WHERE DATE_FORMAT(income_date,"%%Y-%%m") = %s', $month);
        if ( $cat ) $where .= $wpdb->prepare(' AND category = %s', $cat);

        $incomes   = $wpdb->get_results("SELECT * FROM {$this->table} $where ORDER BY income_date DESC");
        $manual_total = floatval($wpdb->get_var("SELECT SUM(amount) FROM {$this->table} $where"));
        $cats      = $wpdb->get_col("SELECT DISTINCT category FROM {$this->table} ORDER BY category");

        // --- Tuition fees from wp_mtti_payments (Completed, same month) ---
        $pay_table  = $wpdb->prefix . 'mtti_payments';
        $fee_total  = 0;
        $fee_count  = 0;
        if ( $wpdb->get_var("SHOW TABLES LIKE '$pay_table'") === $pay_table ) {
            $fee_total = floatval($wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(amount),0) FROM $pay_table
                 WHERE status='Completed' AND DATE_FORMAT(payment_date,'%%Y-%%m') = %s", $month
            )));
            $fee_count = intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $pay_table
                 WHERE status='Completed' AND DATE_FORMAT(payment_date,'%%Y-%%m') = %s", $month
            )));
        }

        // --- Expenses same month ---
        $exp_table    = $wpdb->prefix . 'mtti_expenses';
        $exp_total    = 0;
        if ( $wpdb->get_var("SHOW TABLES LIKE '$exp_table'") === $exp_table ) {
            $exp_total = floatval($wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(amount),0) FROM $exp_table
                 WHERE DATE_FORMAT(expense_date,'%%Y-%%m') = %s", $month
            )));
        }

        $total_income = $manual_total + $fee_total;
        $profit       = $total_income - $exp_total;
        $profit_color = $profit >= 0 ? '#3d6318' : '#d63638';
        $profit_label = $profit >= 0 ? '✅ Profit' : '⚠️ Loss';

        $edit = null;
        if ( ! empty( $_GET['edit_id'] ) )
            $edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE income_id = %d", intval($_GET['edit_id'])));

        $categories = ['Tuition Fees','Registration Fees','Exam Fees','Short Course','Grant','Donation','Rental','Other'];
        $methods    = ['Cash','M-Pesa','Bank Transfer','Cheque','Card','NCBA Paybill'];
        ?>
        <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            💰 Income & P&L
            <button class="button button-primary" onclick="var f=document.getElementById('mtti-inc-form');f.style.display=f.style.display==='none'?'block':'none'">+ Add Income</button>
        </h1>

        <!-- Month selector -->
        <form method="GET" style="margin-bottom:20px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <input type="hidden" name="page" value="mtti-mis-income">
            <label style="font-weight:600;">Month:</label>
            <input type="month" name="month" value="<?php echo esc_attr($month); ?>" style="height:34px;padding:0 8px;">
            <select name="cat" style="height:34px;">
                <option value="">All Categories</option>
                <?php foreach($cats as $c): ?><option value="<?php echo esc_attr($c); ?>" <?php selected($cat,$c); ?>><?php echo esc_html($c); ?></option><?php endforeach; ?>
            </select>
            <button class="button">View</button>
        </form>

        <!-- P&L Summary cards -->
        <div style="display:flex;gap:15px;margin-bottom:25px;flex-wrap:wrap;">
            <!-- Tuition from payments -->
            <div style="background:#fff;border-left:4px solid #0073aa;padding:15px 20px;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.1);min-width:200px;">
                <div style="font-size:11px;color:#777;text-transform:uppercase;">Student Fees (<?php echo $fee_count; ?> payments)</div>
                <div style="font-size:22px;font-weight:700;color:#0073aa;">KES <?php echo number_format($fee_total,2); ?></div>
                <div style="font-size:11px;color:#aaa;">Auto from Payments</div>
            </div>
            <!-- Manual income -->
            <div style="background:#fff;border-left:4px solid #FF9700;padding:15px 20px;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.1);min-width:200px;">
                <div style="font-size:11px;color:#777;text-transform:uppercase;">Other Income</div>
                <div style="font-size:22px;font-weight:700;color:#FF9700;">KES <?php echo number_format($manual_total,2); ?></div>
            </div>
            <!-- Total income -->
            <div style="background:#fff;border-left:4px solid #3d6318;padding:15px 20px;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.1);min-width:200px;">
                <div style="font-size:11px;color:#777;text-transform:uppercase;">Total Income</div>
                <div style="font-size:22px;font-weight:700;color:#3d6318;">KES <?php echo number_format($total_income,2); ?></div>
            </div>
            <!-- Expenses -->
            <div style="background:#fff;border-left:4px solid #d63638;padding:15px 20px;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.1);min-width:200px;">
                <div style="font-size:11px;color:#777;text-transform:uppercase;">Total Expenses</div>
                <div style="font-size:22px;font-weight:700;color:#d63638;">KES <?php echo number_format($exp_total,2); ?></div>
            </div>
            <!-- Profit/Loss -->
            <div style="background:<?php echo $profit>=0?'#f0faf0':'#fff5f5'; ?>;border-left:4px solid <?php echo $profit_color; ?>;padding:15px 20px;border-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.1);min-width:200px;">
                <div style="font-size:11px;color:#777;text-transform:uppercase;"><?php echo $profit_label; ?></div>
                <div style="font-size:26px;font-weight:700;color:<?php echo $profit_color; ?>;">KES <?php echo number_format(abs($profit),2); ?></div>
                <div style="font-size:11px;color:#777;"><?php echo date('F Y', strtotime($month.'-01')); ?></div>
            </div>
        </div>

        <!-- Add/Edit form -->
        <div id="mtti-inc-form" style="display:<?php echo $edit?'block':'none'; ?>;background:#fff;padding:20px;border:1px solid #ddd;border-radius:4px;margin-bottom:20px;">
            <h2><?php echo $edit?'✏️ Edit Income':'➕ Record Income'; ?></h2>
            <form method="POST">
                <?php wp_nonce_field('mtti_income_action','mtti_income_nonce'); ?>
                <input type="hidden" name="action_type" value="<?php echo $edit?'edit':'add'; ?>">
                <?php if($edit): ?><input type="hidden" name="income_id" value="<?php echo $edit->income_id; ?>"><?php endif; ?>
                <table class="form-table" style="max-width:700px;">
                    <tr><th>Date *</th><td><input type="date" name="income_date" required value="<?php echo esc_attr($edit->income_date??date('Y-m-d')); ?>" class="regular-text"></td></tr>
                    <tr><th>Category *</th><td><select name="category" required><?php foreach($categories as $c): ?><option value="<?php echo $c; ?>" <?php selected($edit->category??'',$c); ?>><?php echo $c; ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th>Description *</th><td><input type="text" name="description" required value="<?php echo esc_attr($edit->description??''); ?>" class="regular-text"></td></tr>
                    <tr><th>Amount (KES) *</th><td><input type="number" name="amount" required step="0.01" min="0" value="<?php echo esc_attr($edit->amount??''); ?>" class="regular-text"></td></tr>
                    <tr><th>Received From</th><td><input type="text" name="received_from" value="<?php echo esc_attr($edit->received_from??''); ?>" class="regular-text"></td></tr>
                    <tr><th>Payment Method</th><td><select name="payment_method"><?php foreach($methods as $m): ?><option value="<?php echo $m; ?>" <?php selected($edit->payment_method??'Cash',$m); ?>><?php echo $m; ?></option><?php endforeach; ?></select></td></tr>
                    <tr><th>Reference</th><td><input type="text" name="reference" value="<?php echo esc_attr($edit->reference??''); ?>" class="regular-text"></td></tr>
                    <tr><th>Notes</th><td><textarea name="notes" rows="2" class="regular-text"><?php echo esc_textarea($edit->notes??''); ?></textarea></td></tr>
                </table>
                <p><button type="submit" class="button button-primary"><?php echo $edit?'Update':'Save Income'; ?></button> <a href="?page=mtti-mis-income" class="button">Cancel</a></p>
            </form>
        </div>

        <!-- Manual income table -->
        <h3 style="margin-top:0;">Other Income Entries</h3>
        <table class="wp-list-table widefat fixed striped" style="font-size:13px;">
            <thead><tr>
                <th width="90">Date</th><th width="130">Category</th><th>Description</th>
                <th width="130">Received From</th><th width="90">Method</th>
                <th width="110">Reference</th>
                <th width="120" style="text-align:right;">Amount (KES)</th>
                <th width="100">Actions</th>
            </tr></thead>
            <tbody>
            <?php if(empty($incomes)): ?>
                <tr><td colspan="8" style="text-align:center;color:#777;padding:20px;">No manual income entries for this month. Student fee payments are auto-included in the summary above.</td></tr>
            <?php else: foreach($incomes as $inc): ?>
                <tr>
                    <td><?php echo esc_html($inc->income_date); ?></td>
                    <td><span style="background:#f0f0f0;padding:2px 8px;border-radius:3px;font-size:11px;"><?php echo esc_html($inc->category); ?></span></td>
                    <td><?php echo esc_html($inc->description); ?><?php if($inc->notes): ?><br><small style="color:#777;"><?php echo esc_html(substr($inc->notes,0,60)); ?></small><?php endif; ?></td>
                    <td><?php echo esc_html($inc->received_from); ?></td>
                    <td><?php echo esc_html($inc->payment_method); ?></td>
                    <td><?php echo esc_html($inc->reference); ?></td>
                    <td style="text-align:right;font-weight:600;"><?php echo number_format($inc->amount,2); ?></td>
                    <td>
                        <a href="?page=mtti-mis-income&month=<?php echo $month; ?>&edit_id=<?php echo $inc->income_id; ?>" class="button button-small">Edit</a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this entry?')">
                            <?php wp_nonce_field('mtti_income_action','mtti_income_nonce'); ?>
                            <input type="hidden" name="action_type" value="delete">
                            <input type="hidden" name="income_id" value="<?php echo $inc->income_id; ?>">
                            <button type="submit" class="button button-small" style="color:#d63638;">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <?php if(!empty($incomes)): ?>
            <tfoot><tr>
                <td colspan="6" style="text-align:right;font-weight:700;padding:10px;">SUBTOTAL:</td>
                <td style="text-align:right;font-weight:700;color:#FF9700;">KES <?php echo number_format($manual_total,2); ?></td>
                <td></td>
            </tr></tfoot>
            <?php endif; ?>
        </table>
        </div>
        <?php
    }
}
