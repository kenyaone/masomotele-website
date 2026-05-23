<?php
defined('ABSPATH') || die();

require_once WLSM_PLUGIN_DIR_PATH . 'includes/helpers/staff/WLSM_M_Staff_Accountant.php';

require_once WLSM_PLUGIN_DIR_PATH . 'public/inc/account/student/partials/navigation.php';

// Checks if student exists.
$student_id = $student->ID;
$student    = WLSM_M_Staff_General::fetch_student($school_id, $session_id, $student_id);

if (! $student) {
    throw new Exception(esc_html__('Student not found.', 'school-management'));
}

$fee_structure = WLSM_M_Staff_Accountant::fetch_student_assigned_fees($school_id, $student_id);


$fees     = WLSM_M_Staff_Accountant::fetch_student_fees($school_id, $student_id);
$invoices = WLSM_M_Staff_Accountant::get_student_invoices($student_id);
$payments = WLSM_M_Staff_Accountant::get_student_payments($student_id);

$class_label = WLSM_M_Class::get_label_text($student->class_label);
$start_date  = $student->start_date;
$end_date    = $student->end_date;

// get start_date and end_date difference in months
$start_date        = new DateTime($start_date);
$end_date          = new DateTime($end_date);
$interval          = $start_date->diff($end_date);
$months_in_session = $interval->format('%m');

$interval = $start_date->diff($end_date);
// Calculate total months including years
$months_in_session = ($interval->y * 12) + $interval->m;

// If you want to consider partial months (days)
if ($interval->d > 0) {
    ++$months_in_session; // Add one more month if there are remaining days
}

?>


<div class="wlsm-content-area wlsm-section-fee-invoices wlsm-student-fee-invoices">
    <div class="wlsm-st-main-title">
        <span>
            <?php esc_html_e('Fee Structure', 'school-management'); ?>
        </span>
    </div>

    <!-- Print fee structure section. -->
    <div class="wlsm-container wlsm wlsm-form-section" id="wlsm-print-fee-structure">
        <div class="wlsm-print-fee-structure-container">
            <div class="table-responsive w-100">
                <table class="table table-bordered wlsm-view-fee-structure">
                    <thead>
                        <tr>
                            <th class="text-nowrap"><?php esc_html_e('Fee Type', 'school-management'); ?></th>
                            <th class="text-nowrap"><?php esc_html_e('Amount', 'school-management'); ?></th>
                            <th class="text-nowrap"><?php esc_html_e('Period', 'school-management'); ?></th>
                            <th class="text-nowrap"><?php esc_html_e('Payment Occurrences', 'school-management'); ?></th>
                            <th class="text-nowrap"><?php esc_html_e('Session Total', 'school-management'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $session_onetime_total      = 0;
                        $session_quarterly_total    = 0;
                        $session_quadrimester_total = 0;
                        $session_half_yearly_total  = 0;
                        $session_monthly_total      = 0;
                        $session_yearly_total       = 0;
                        foreach ($fees as $key => $fee) {
                        ?>
                            <tr>
                                <td><?php echo esc_html(WLSM_M_Staff_Accountant::get_label_text($fee->label)); ?></td>
                                <td><?php echo esc_html(WLSM_Config::get_money_text($fee->amount, $school_id)); ?></td>
                                <td><?php echo esc_html(WLSM_M_Staff_Accountant::get_fee_period_text($fee->period)); ?></td>
                                <td>
                                    <?php
                                    if ($fee->period == 'monthly') {
                                        echo esc_html($months_in_session);
                                    } elseif ($fee->period == 'one-time') {
                                        echo esc_html('1');
                                    } elseif ($fee->period == 'quarterly') {
                                        echo esc_html(ceil($months_in_session / 3)); // Fixed: quarterly is 3 months, not 4
                                    } elseif ($fee->period == 'quadrimester') {
                                        echo esc_html(ceil($months_in_session / 4)); // Fixed: quadrimester is 4 months
                                    } elseif ($fee->period == 'half-yearly') {
                                        echo esc_html(ceil($months_in_session / 6));
                                    } elseif ($fee->period == 'annually') {
                                        echo esc_html(ceil($months_in_session / 12));
                                    } else {
                                        echo esc_html('-');
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if ($fee->period == "monthly") {
                                        $session_monthly_total += intval($fee->amount) * $months_in_session;
                                        echo esc_html(WLSM_Config::get_money_text(intval($fee->amount) * $months_in_session, $school_id));
                                    } elseif ($fee->period == 'one-time') {
                                        $session_onetime_total += intval($fee->amount);
                                        echo esc_html(WLSM_Config::get_money_text($fee->amount, $school_id));
                                    } elseif ($fee->period == 'quarterly') {
                                        $quarters = ceil($months_in_session / 3); // Fixed: quarterly is 3 months
                                        $session_quarterly_total += intval($fee->amount) * $quarters;
                                        echo esc_html(WLSM_Config::get_money_text(($fee->amount) * $quarters, $school_id));
                                    } elseif ($fee->period == 'quadrimester') {
                                        $quadrimesters = ceil($months_in_session / 4); // Fixed: quadrimester is 4 months
                                        $session_quadrimester_total += intval($fee->amount) * $quadrimesters;
                                        echo esc_html(WLSM_Config::get_money_text(($fee->amount) * $quadrimesters, $school_id)); // Fixed label
                                    } elseif ($fee->period == 'half-yearly') {
                                        $half_years = ceil($months_in_session / 6);
                                        $session_half_yearly_total += intval($fee->amount) * $half_years;
                                        echo esc_html(WLSM_Config::get_money_text(($fee->amount) * $half_years, $school_id));
                                    } elseif ($fee->period == 'annually') {
                                        $years = ceil($months_in_session / 12);
                                        $yearly_amount = intval($fee->amount) * $years;
                                        $session_yearly_total += $yearly_amount; // Fixed: calculating correctly by years
                                        echo esc_html(WLSM_Config::get_money_text($yearly_amount, $school_id));
                                    }
                                    ?>
                                </td>
                            </tr>

                        <?php
                        }

                        $total_session_fee = $session_monthly_total + $session_onetime_total + $session_quarterly_total + $session_quadrimester_total + $session_half_yearly_total + $session_yearly_total;
                        $student_concession = WLSM_M_Staff_General::fetch_student_concession($student->ID, $student->session_id, $school_id);
                        $concession_amount = 0;
                        $concession_label = '';
                        $payable_amount = $total_session_fee;

                        if ($student_concession && 'approved' === $student_concession->status) {
                            if ('percentage' === $student_concession->concession_type) {
                                $concession_amount = ($total_session_fee * $student_concession->percentage_value) / 100;
                                $concession_label = sprintf(
                                    '%s (%s)',
                                    $student_concession->concession_name,
                                    $student_concession->percentage_value . '%'
                                );
                            } elseif ('fixed_amount' === $student_concession->concession_type) {
                                $concession_amount = min($student_concession->fixed_amount, $total_session_fee);
                                $concession_label = sprintf(
                                    '%s (%s)',
                                    $student_concession->concession_name,
                                    __('Fixed', 'school-management')
                                );
                            }

                            $payable_amount = max($total_session_fee - $concession_amount, 0);
                        }
                        ?>
                        <tr>
                            <td colspan="3" class="text-right"><strong><?php esc_html_e('Fee Type Total:', 'school-management'); ?></strong></td>
                            <td colspan="2" class="text-left"><strong><?php echo esc_html(WLSM_Config::get_money_text($total_session_fee, $school_id)); ?></strong></td>
                        </tr>
                        <?php if ($concession_amount > 0) : ?>
                            <tr>
                                <td colspan="3" class="text-right"><strong><?php echo esc_html($concession_label . ':'); ?></strong></td>
                                <td colspan="2" class="text-left"><strong>- <?php echo esc_html(WLSM_Config::get_money_text($concession_amount, $school_id)); ?></strong></td>
                            </tr>
                            <tr>
                                <td colspan="3" class="text-right"><strong><?php esc_html_e('Payable Amount', 'school-management'); ?>:</strong></td>
                                <td colspan="2" class="text-left"><strong><?php echo esc_html(WLSM_Config::get_money_text($payable_amount, $school_id)); ?></strong></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
</div>
