<?php
session_start();
require_once 'config.php';

// 分页处理
$current_page = max(1, (int)($_GET['page'] ?? 1));
$all_messages = MessageHandler::getAllMessages();
$total_messages = count($all_messages);
$total_pages = ceil($total_messages / Config::MESSAGES_PER_PAGE);
$offset = ($current_page - 1) * Config::MESSAGES_PER_PAGE;
$messages = array_slice($all_messages, $offset, Config::MESSAGES_PER_PAGE);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/static/favicon.svg" type="image/svg+xml">
    <title>iTxGo🍃 - 留言板</title>
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
    <div class="container">
        <header class="site-header">
            <h1 class="fat-round-title">iTxGo.co〽︎</h1>
            <nav class="site-nav">
                <a href="https://comhh.com"><svg xmlns="http://www.w3.org/2000/svg" width="1.3em" height="1.3em" viewBox="0 0 24 24"><path fill="currentColor" d="M4 21V9l8-6l8 6v12h-6v-7h-4v7z"/></svg>&nbsp;首页</a>
                <a href="https://blog.comhh.com"><svg xmlns="http://www.w3.org/2000/svg" width="1.2em" height="1.2em" viewBox="0 0 512 512"><path fill="currentColor" d="M224 24c0-13.3 10.7-24 24-24c145.8 0 264 118.2 264 264c0 13.3-10.7 24-24 24s-24-10.7-24-24c0-119.3-96.7-216-216-216c-13.3 0-24-10.7-24-24M80 96c26.5 0 48 21.5 48 48v224c0 26.5 21.5 48 48 48s48-21.5 48-48s-21.5-48-48-48c-8.8 0-16-7.2-16-16v-64c0-8.8 7.2-16 16-16c79.5 0 144 64.5 144 144s-64.5 144-144 144S32 447.5 32 368V144c0-26.5 21.5-48 48-48m168 0c92.8 0 168 75.2 168 168c0 13.3-10.7 24-24 24s-24-10.7-24-24c0-66.3-53.7-120-120-120c-13.3 0-24-10.7-24-24s10.7-24 24-24"/></svg>&nbsp;博客</a>
                <a href="https://tv.wllgo.com"><svg xmlns="http://www.w3.org/2000/svg" width="1.3em" height="1.3em" viewBox="0 0 24 24"><path fill="currentColor" fill-rule="evenodd" d="M22 16v-4c0-2.828 0-4.243-.879-5.121c-.825-.826-2.123-.876-4.621-.879v16c2.498-.003 3.796-.053 4.621-.879c.879-.878.879-2.293.879-5.12m-3-5a1 1 0 1 1 0 2a1 1 0 0 1 0-2m0 4a1 1 0 1 1 0 2a1 1 0 0 1 0-2" clip-rule="evenodd"/><path fill="currentColor" d="M15.57 3.488L13.415 6H15v16H8c-2.828 0-4.243 0-5.121-.879C2 20.243 2 18.828 2 16.001v-4c0-2.83 0-4.244.879-5.122C3.757 6 5.172 6 8 6h2.584L8.43 3.488a.75.75 0 0 1 1.138-.976L12 5.348l2.43-2.836a.75.75 0 0 1 1.14.976"/></svg>&nbsp;影视</a>
                <a href="https://bbs.itxgo.com"><svg xmlns="http://www.w3.org/2000/svg" width="1.3em" height="1.3em" viewBox="0 0 24 24"><path fill="currentColor" d="M7 18q-.425 0-.712-.288T6 17v-2h13V6h2q.425 0 .713.288T22 7v15l-4-4zm-5-1V3q0-.425.288-.712T3 2h13q.425 0 .713.288T17 3v9q0 .425-.288.713T16 13H6z"/></svg>&nbsp;论坛</a><a href="https://github.com/xpfcom"><svg xmlns="http://www.w3.org/2000/svg" width="1.3em" height="1.3em" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2A10 10 0 0 0 2 12c0 4.42 2.87 8.17 6.84 9.5c.5.08.66-.23.66-.5v-1.69c-2.77.6-3.36-1.34-3.36-1.34c-.46-1.16-1.11-1.47-1.11-1.47c-.91-.62.07-.6.07-.6c1 .07 1.53 1.03 1.53 1.03c.87 1.52 2.34 1.07 2.91.83c.09-.65.35-1.09.63-1.34c-2.22-.25-4.55-1.11-4.55-4.92c0-1.11.38-2 1.03-2.71c-.1-.25-.45-1.29.1-2.64c0 0 .84-.27 2.75 1.02c.79-.22 1.65-.33 2.5-.33s1.71.11 2.5.33c1.91-1.29 2.75-1.02 2.75-1.02c.55 1.35.2 2.39.1 2.64c.65.71 1.03 1.6 1.03 2.71c0 3.82-2.34 4.66-4.57 4.91c.36.31.69.92.69 1.85V21c0 .27.16.59.67.5C19.14 20.16 22 16.42 22 12A10 10 0 0 0 12 2"/></svg>&nbsp;项目</a>
                <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                    <a href="admin.php?logout=1"><svg xmlns="http://www.w3.org/2000/svg" width="1.3em" height="1.3em" viewBox="0 0 24 24"><g fill="none"><path d="m12.593 23.258l-.011.002l-.071.035l-.02.004l-.014-.004l-.071-.035q-.016-.005-.024.005l-.004.01l-.017.428l.005.02l.01.013l.104.074l.015.004l.012-.004l.104-.074l.012-.016l.004-.017l-.017-.427q-.004-.016-.017-.018m.265-.113l-.013.002l-.185.093l-.01.01l-.003.011l.018.43l.005.012l.008.007l.201.093q.019.005.029-.008l.004-.014l-.034-.614q-.005-.018-.02-.022m-.715.002a.02.02 0 0 0-.027.006l-.006.014l-.034.614q.001.018.017.024l.015-.002l.201-.093l.01-.008l.004-.011l.017-.43l-.003-.012l-.01-.01z"/><path fill="currentColor" d="M12 2.5a1.5 1.5 0 0 1 0 3H7a.5.5 0 0 0-.5.5v12a.5.5 0 0 0 .5.5h4.5a1.5 1.5 0 0 1 0 3H7A3.5 3.5 0 0 1 3.5 18V6A3.5 3.5 0 0 1 7 2.5Zm6.06 5.61l2.829 2.83a1.5 1.5 0 0 1 0 2.12l-2.828 2.83a1.5 1.5 0 1 1-2.122-2.122l.268-.268H12a1.5 1.5 0 0 1 0-3h4.207l-.268-.268a1.5 1.5 0 1 1 2.122-2.121Z"/></g></svg>&nbsp;退出</a>
                <?php else: ?>
                    <a href="admin.php"><svg xmlns="http://www.w3.org/2000/svg" width="1.3em" height="1.3em" viewBox="0 0 24 24"><path fill="currentColor" d="m16 21l-.3-1.5q-.3-.125-.562-.262T14.6 18.9l-1.45.45l-1-1.7l1.15-1q-.05-.35-.05-.65t.05-.65l-1.15-1l1-1.7l1.45.45q.275-.2.538-.337t.562-.263L16 11h2l.3 1.5q.3.125.563.275t.537.375l1.45-.5l1 1.75l-1.15 1q.05.3.05.625t-.05.625l1.15 1l-1 1.7l-1.45-.45q-.275.2-.537.338t-.563.262L18 21zM2 20v-2.8q0-.825.425-1.55t1.175-1.1q1.275-.65 2.875-1.1T10 13h.35q.15 0 .3.05q-.725 1.8-.6 3.575T11.25 20zm15-2q.825 0 1.413-.587T19 16t-.587-1.412T17 14t-1.412.588T15 16t.588 1.413T17 18m-7-6q-1.65 0-2.825-1.175T6 8t1.175-2.825T10 4t2.825 1.175T14 8t-1.175 2.825T10 12"/></svg>&nbsp;管理</a>
                <?php endif; ?>
            </nav>
        </header>

        <main class="content">
            <div class="guestbook-container">
                <!-- 留言输入卡片 -->
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">发表留言</h2>&nbsp;<svg xmlns="http://www.w3.org/2000/svg" width="1.6em" height="1.2em" viewBox="0 0 40 40"><g fill="none" stroke-miterlimit="10"><path fill="#ffe236" stroke="#231f20" d="M21.29 6.83c-4 4-8.27 9.25-8.67 10.64a2.7 2.7 0 0 0-.34.88c-2.55-.28-5.19.71-7.69 3.22C0 26.15-.32 36.3 1.64 38.27s12.12 1.64 16.7-3c2.51-2.5 3.5-5.14 3.22-7.69a3.1 3.1 0 0 0 .88-.34c1.39-.4 6.67-4.7 10.64-8.67C40.93 10.77 40.8 7 36.87 3S28.53-.41 21.29 6.83Z" stroke-width="1"/><path stroke="#231f20" stroke-linecap="round" d="M15.1 20a14 14 0 0 1 2.67 2.2A13.8 13.8 0 0 1 20 24.87" stroke-width="1"/><path fill="#fff" stroke="#231f20" d="M8.69 28.44a2.78 2.78 0 1 0 5.56 0a2.78 2.78 0 0 0-5.56 0Z" stroke-width="1"/><path stroke="#231f20" stroke-linecap="round" d="m9.5 30.41l-3.93 3.93" stroke-width="1"/><path stroke="#fff" stroke-linecap="round" d="M28.28 5c2.85-2 3.17-1.94 4.6-1.17" stroke-width="1"/></g></svg>
                        <p class="card-subtitle">欢迎留下您的意见和建议</p>
                        
                        <form action="post.php" method="post" class="form" id="messageForm">
                            <div class="form-group">
                                <label for="name" class="form-label">昵称：</label>
                                <input type="text" id="name" name="name" class="form-control" 
                                       maxlength="<?= Config::MAX_NAME_LENGTH ?>" required 
                                       placeholder="请输入您的昵称">
                                <div class="char-counter" id="nameCounter">0/<?= Config::MAX_NAME_LENGTH ?></div>
                            </div>
                            <div class="form-group">
                                <label for="message" class="form-label">内容：</label>
                                <textarea id="message" name="message" class="form-control" rows="4" 
                                          maxlength="<?= Config::MAX_MESSAGE_LENGTH ?>" required
                                          placeholder="请输入留言内容..."></textarea>
                                <div class="char-counter" id="messageCounter">0/<?= Config::MAX_MESSAGE_LENGTH ?></div>
                            </div>
                            <input type="hidden" id="client_time" name="client_time" value="">
                            <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">提交留言</button>
                        </form>
                    </div>
                </div>

                <!-- 留言列表卡片 -->
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">留言列表</h2>&nbsp;<svg xmlns="http://www.w3.org/2000/svg" width="1.5em" height="1.4em" viewBox="0 0 24 24"><g fill="none"><path fill="#ffed32" stroke="#191919" stroke-linecap="round" stroke-linejoin="round" d="M21.09 3.864a.957.957 0 0 0-.957-.957H14.92a3.186 3.186 0 0 0-5.836 0H3.867a.957.957 0 0 0-.956.957v18.18a.957.957 0 0 0 .956.956h16.266a.957.957 0 0 0 .957-.957z" stroke-width="1"/><path fill="#e3e3e3" d="M5.781 20.13h8.688a1.91 1.91 0 0 0 1.453-.67l1.836-2.142c.297-.347.46-.788.462-1.244V5.778H5.78z"/><path fill="#fff" d="M5.781 18.204L18.21 5.773H5.781z"/><path stroke="#191919" stroke-linecap="round" stroke-linejoin="round" d="M5.781 20.13h8.688a1.91 1.91 0 0 0 1.453-.67l1.836-2.142c.297-.347.46-.788.462-1.244V5.778H5.78zM15.35 8.648h-4.306m-2.393 0h.479m6.22 3.827h-4.306m-2.393 0h.479m4.784 3.827h-2.87m-2.393 0h.479" stroke-width="1"/></g></svg>
                        <p class="card-subtitle">共 <?= $total_messages ?> 条留言</p>
                        
                        <div class="messages-section">
                            <?php if (empty($messages)): ?>
                                <p class="no-messages">暂无留言！</p>
                            <?php else: ?>
                                <?php foreach ($messages as $message): ?>
                                    <div class="message-item">
                                        <div class="message-header">
                                            <span class="message-name"><?= Utils::escape($message['name']) ?></span>
                                            <span class="message-time"><?= Utils::escape($message['time']) ?></span>
                                        </div>
                                        
                                        <?php 
                                        $has_long_numbers = preg_match('/[0-9]{50,}/', $message['content']);
                                        $content_class = $has_long_numbers ? 'message-content long-numbers' : 'message-content';
                                        ?>
                                        <div class="<?= $content_class ?>"><?= Utils::safeDisplay($message['content']) ?></div>
                                        
                                        <!-- 回复部分 -->
                                        <?php if (!empty($message['replies'])): ?>
                                            <div class="reply-section">
                                                <?php foreach ($message['replies'] as $reply): ?>
                                                    <div class="reply-item">
                                                        <div class="reply-header">
                                                            <span>管理员回复 <span class="admin-label">ADMIN</span></span>
                                                            <span class="message-time"><?= Utils::escape($reply['time']) ?></span>
                                                        </div>
                                                        <div class="reply-content"><?= Utils::safeDisplay($reply['content']) ?></div>
                                                        
                                                        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                                                            <div class="message-actions">
                                                                <a href="admin.php?delete=1&file=<?= urlencode($message['file']) ?>&message_id=<?= urlencode($message['id']) ?>&reply_id=<?= urlencode($reply['id']) ?>" 
                                                                   class="btn btn-danger btn-sm delete-link">删除回复</a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']): ?>
                                            <div class="message-actions">
                                                <button type="button" class="btn btn-secondary btn-sm reply-toggle-btn">回复</button>
                                                <a href="admin.php?delete=1&file=<?= urlencode($message['file']) ?>&message_id=<?= urlencode($message['id']) ?>" 
                                                   class="btn btn-danger btn-sm delete-link">删除留言</a>
                                            </div>
                                            
                                            <div class="reply-form" data-file="<?= Utils::escape($message['file']) ?>" data-message-id="<?= Utils::escape($message['id']) ?>">
                                                <div class="form-group">
                                                    <textarea class="form-control reply-textarea" rows="3" 
                                                              placeholder="输入回复内容..." 
                                                              maxlength="<?= Config::MAX_MESSAGE_LENGTH ?>"></textarea>
                                                </div>
                                                <div class="button-group">
                                                    <button type="button" class="btn btn-primary btn-sm submit-reply-btn">提交回复</button>
                                                    <button type="button" class="btn btn-light btn-sm cancel-reply-btn">取消</button>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- 分页导航 -->
                        <?php if ($total_pages > 1): ?>
                            <div class="pagination">
                                <?php if ($current_page > 1): ?>
                                    <a href="?page=1">首页</a>
                                    <a href="?page=<?= $current_page - 1 ?>">上一页</a>
                                <?php else: ?>
                                    <span class="disabled">首页</span>
                                    <span class="disabled">上一页</span>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $current_page - 2);
                                $end = min($total_pages, $current_page + 2);
                                
                                for ($i = $start; $i <= $end; $i++):
                                ?>
                                    <?php if ($i == $current_page): ?>
                                        <span class="current"><?= $i ?></span>
                                    <?php else: ?>
                                        <a href="?page=<?= $i ?>"><?= $i ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <?php if ($current_page < $total_pages): ?>
                                    <a href="?page=<?= $current_page + 1 ?>">下一页</a>
                                    <a href="?page=<?= $total_pages ?>">末页</a>
                                <?php else: ?>
                                    <span class="disabled">下一页</span>
                                    <span class="disabled">末页</span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>

        <footer class="site-footer">
            <p>© 2025 <a href="https://github.com/xpfcom/gbook" class="site-footer" style="text-decoration: none;">Gbook</a> by <a href="https://itxgo.com" class="site-footer" style="text-decoration: none;">iTxGo.com</a>&nbsp;·&nbsp;All rights reserved&nbsp;·&nbsp;「 感恩 <span id="visit-count"></span> 次相遇 」</p>
        </footer>
        <!-- 用 fetch API 异步请求 PHP 统计 -->
        <script>
            fetch('https://comhh.com/counter.php')
                .then(response => {
                    if (!response.ok) throw new Error('统计加载失败');
                    return response.text();
                })
                .then(count => {
                    document.getElementById('visit-count').textContent = count;
                })
                .catch(error => {
                    console.error(error);
                    document.getElementById('visit-count').textContent = '∞'; // 失败时显示占位符
                });
        </script>
    </div>

    <script src="app.js?v=<?= time() ?>"></script>
</body>
</html>