<?php
session_start();
require_once 'config.php';

// 检查管理员是否登录
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    header('HTTP/1.1 403 Forbidden');
    die('无权访问');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 防止重复提交
        Utils::checkSubmitInterval('last_reply_time');
        
        // 获取表单数据
        $messageFile = $_POST['file'] ?? '';
        $messageId = $_POST['message_id'] ?? '';
        $content = $_POST['content'] ?? '';
        $time = $_POST['time'] ?? '';
        
        // 保存回复
        MessageHandler::saveReply($messageFile, $messageId, $content, $time);
        
        echo '回复成功';
        
    } catch (Exception $e) {
        header('HTTP/1.1 400 Bad Request');
        die($e->getMessage());
    }
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    die('Method Not Allowed');
}