<?php
/**
 * 预约提醒定时任务
 * 建议每小时执行一次：0 * * * * /usr/bin/php /path/to/appointment-reminders.php
 */

require_once dirname(__DIR__) . '/includes/init.php';
require_once dirname(__DIR__) . '/includes/AppointmentNotification.php';

// 确保只在命令行模式下运行
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line.');
}

$notification = new AppointmentNotification();

echo "[" . date('Y-m-d H:i:s') . "] Starting appointment reminder task...\n";

try {
    // 发送24小时提醒
    echo "Sending 24-hour reminders...\n";
    $result24h = $notification->sendBatchReminders('24h');
    echo "24h reminders: {$result24h['success']}/{$result24h['total']} sent successfully\n";
    
    // 发送2小时提醒
    echo "Sending 2-hour reminders...\n";
    $result2h = $notification->sendBatchReminders('2h');
    echo "2h reminders: {$result2h['success']}/{$result2h['total']} sent successfully\n";
    
    // 发送30分钟提醒
    echo "Sending 30-minute reminders...\n";
    $result30m = $notification->sendBatchReminders('30m');
    echo "30m reminders: {$result30m['success']}/{$result30m['total']} sent successfully\n";
    
    $totalSent = $result24h['success'] + $result2h['success'] + $result30m['success'];
    $totalAttempted = $result24h['total'] + $result2h['total'] + $result30m['total'];
    
    echo "Total: {$totalSent}/{$totalAttempted} reminders sent successfully\n";
    
    // 记录到日志
    error_log("[Appointment Reminders] {$totalSent}/{$totalAttempted} reminders sent at " . date('Y-m-d H:i:s'));
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("[Appointment Reminders Error] " . $e->getMessage());
}

echo "[" . date('Y-m-d H:i:s') . "] Appointment reminder task completed.\n";
?>