<?php
session_start();
require_once 'config.php';

// 处理登录
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    try {
        $username = Utils::validateLength($_POST['username'] ?? '', 50, '用户名');
        $password = Utils::validateLength($_POST['password'] ?? '', 100, '密码');
        
        if ($username === Config::ADMIN_USERNAME && $password === Config::ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            header('Location: index.php');
            exit;
        } else {
            $error = "用户名或密码错误";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// 处理登出
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// 处理删除留言或回复
if (isset($_GET['delete']) && isset($_GET['file']) && isset($_GET['message_id']) && 
    isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    
    $filename = basename($_GET['file']);
    $messageId = $_GET['message_id'];
    $replyId = $_GET['reply_id'] ?? null;
    
    try {
        MessageHandler::deleteItem($filename, $messageId, $replyId);
        
        // 智能重定向
        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        
        // 检查当前页面是否还有内容
        if (preg_match('/[?&]page=(\d+)/', $referer, $matches)) {
            $currentPage = (int)$matches[1];
            $allMessages = MessageHandler::getAllMessages();
            $totalMessages = count($allMessages);
            $totalPages = ceil($totalMessages / Config::MESSAGES_PER_PAGE);
            
            // 如果当前页面超出范围，重定向到合适的页面
            if ($currentPage > $totalPages && $totalPages > 0) {
                $referer = preg_replace('/([?&])page=\d+/', '$1page=' . $totalPages, $referer);
            } elseif ($totalMessages == 0) {
                $referer = preg_replace('/[?&]page=\d+/', '', $referer);
                $referer = rtrim($referer, '?&');
                if (strpos($referer, '?') === false && strpos($referer, 'index.php') !== false) {
                    $referer = 'index.php';
                }
            }
        }
        
        header('Location: ' . $referer);
        exit;
        
    } catch (Exception $e) {
        Utils::renderError('删除失败', $e->getMessage());
        exit;
    }
}

// 如果不是管理员，显示登录表单
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']):
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>iTxGo🍃 - 留言板 - 管理登录</title>
    <link rel="icon" href="/static/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <!-- 引入动态背景1 JS -->
    <script src="/static/BlurGradientBg.js"></script>

    <!-- 动态背景容器，初始隐藏 -->
    <div id="box" style="
      position:fixed;
      top:0;
      left:0;
      width:100vw;
      height:100vh;
      z-index:-1;
      opacity:0;
      transition: opacity 1s ease;
      background-color: transparent;
    "></div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
      let colorbg = new Color4Bg.BlurGradientBg({
        dom: "box",
        colors: ["#86DFE9","#A4EFF4","#FDFFF0","#D6F2C7"],
        loop: true
      });

      // 渐入效果：延迟设置透明度
      const box = document.getElementById("box");
      setTimeout(() => {
        box.style.opacity = "0.5";
      }, 100); // 延迟一点时间，确保内容已渲染
    });
    </script>
</head>
<body>
    <div class="centered-container">
        <div class="card login-card">
            <div class="card-body">
                <h2 class="card-title">👤管理登录</h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= Utils::escape($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="form">
                    <div class="form-group">
                        <label for="username" class="form-label">用户名：</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               required placeholder="请输入管理员用户名">
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">密码：</label>
                        <input type="password" id="password" name="password" class="form-control" 
                               required placeholder="请输入管理员密码">
                    </div>
                    <button type="submit" name="login" class="btn btn-primary btn-lg" style="width: 100%;">登录</button>
                </form>
                
                <div style="margin-top: 20px; text-align: center;">
                    <a href="index.php" class="btn btn-light" style="width: 100%;">返回留言板</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php
exit;
endif;
?>