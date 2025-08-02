<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // 防止重复提交
        Utils::checkSubmitInterval('last_submit_time');
        
        // 获取和验证表单数据
        $name = $_POST['name'] ?? '';
        $message = $_POST['message'] ?? '';
        $clientTime = $_POST['client_time'] ?? '';
        
        // 保存留言
        MessageHandler::saveMessage($name, $message, $clientTime);
        
        // 重定向回留言板
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        // 显示错误页面
        Utils::renderError('提交失败', $e->getMessage());
        exit;
    }
} else {
    // 如果不是POST请求，重定向到首页
    header('Location: index.php');
    exit;
}