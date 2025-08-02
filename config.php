<?php
// 配置文件
class Config {
    // 管理员配置
    const ADMIN_USERNAME = 'admin';
    const ADMIN_PASSWORD = 'admin'; // 建议使用更复杂的密码
    
    // 分页配置
    const MESSAGES_PER_PAGE = 6;
    
    // 验证规则
    const MAX_NAME_LENGTH = 20;
    const MAX_MESSAGE_LENGTH = 600;
    const MIN_SUBMIT_INTERVAL = 5; // 防止重复提交的时间间隔（秒）
    
    // 文件配置
    const FILE_PERMISSIONS = 0644; // 更安全的文件权限
    const MESSAGE_FILE_PATTERN = '/^(\d{8})\.json$/';
}

// 公共函数
class Utils {
    
    /**
     * 验证字符串长度
     */
    public static function validateLength($str, $maxLength, $fieldName = '字段') {
        $length = mb_strlen(trim($str), 'UTF-8');
        if ($length === 0) {
            throw new Exception("{$fieldName}不能为空");
        }
        if ($length > $maxLength) {
            throw new Exception("{$fieldName}不能超过{$maxLength}个字符");
        }
        return trim($str);
    }
    
    /**
     * 安全的HTML输出
     */
    public static function escape($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 安全显示内容 - 专门处理留言内容的显示
     */
    public static function safeDisplay($content) {
        // 1. 先进行HTML转义防止XSS
        $escaped = self::escape($content);
        
        // 2. 只将单个 \n 替换为 <br>，避免 nl2br() 的复杂处理
        return str_replace("\n", "<br>", $escaped);
    }
    
    /**
     * 验证时间格式
     */
    public static function validateTime($timeStr) {
        if (empty($timeStr)) {
            return date('Y-m-d H:i:s');
        }
        
        $datetime = DateTime::createFromFormat('Y-m-d H:i:s', $timeStr);
        if ($datetime && $datetime->format('Y-m-d H:i:s') === $timeStr) {
            return $timeStr;
        }
        
        return date('Y-m-d H:i:s');
    }
    
    /**
     * 防重复提交检查
     */
    public static function checkSubmitInterval($sessionKey) {
        if (isset($_SESSION[$sessionKey]) && (time() - $_SESSION[$sessionKey] < Config::MIN_SUBMIT_INTERVAL)) {
            throw new Exception('提交过于频繁，请稍后再试');
        }
        $_SESSION[$sessionKey] = time();
    }
    
    /**
     * 获取所有留言文件
     */
    public static function getMessageFiles() {
        $files = [];
        if ($handle = opendir('.')) {
            while (false !== ($entry = readdir($handle))) {
                if (preg_match(Config::MESSAGE_FILE_PATTERN, $entry)) {
                    $files[] = $entry;
                }
            }
            closedir($handle);
        }
        rsort($files); // 按日期倒序
        return $files;
    }
    
    /**
     * 安全写入文件
     */
    public static function writeToFile($filename, $data, $append = true) {
        $flags = LOCK_EX;
        if ($append) {
            $flags |= FILE_APPEND;
        }
        
        $result = file_put_contents($filename, $data, $flags);
        if ($result !== false) {
            chmod($filename, Config::FILE_PERMISSIONS);
            return true;
        }
        return false;
    }
    
    /**
     * 读取 JSON 文件
     */
    public static function readJsonFile($filename) {
        if (!file_exists($filename)) {
            return [];
        }
        
        $content = file_get_contents($filename);
        if ($content === false) {
            return [];
        }
        
        $data = json_decode($content, true);
        return is_array($data) ? $data : [];
    }
    
    /**
     * 写入 JSON 文件
     */
    public static function writeJsonFile($filename, $data) {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new Exception('JSON 编码失败');
        }
        
        if (!self::writeToFile($filename, $json, false)) {
            throw new Exception('文件写入失败');
        }
        
        return true;
    }
    
    /**
     * 迁移旧格式文件到新格式
     */
    public static function migrateOldFormat($oldFilename) {
        $newFilename = str_replace('.txt', '.json', $oldFilename);
        
        if (file_exists($newFilename)) {
            return; // 新格式文件已存在，跳过迁移
        }
        
        if (!file_exists($oldFilename)) {
            return; // 旧文件不存在
        }
        
        $lines = file($oldFilename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return;
        }
        
        $messages = [];
        $messageId = 0;
        
        // 先处理留言
        foreach ($lines as $lineIndex => $line) {
            if (strpos($line, '|reply|') === false) {
                $parts = explode('|', $line, 3);
                if (count($parts) >= 3) {
                    $messages[] = [
                        'id' => $messageId,
                        'name' => $parts[0],
                        'content' => $parts[1],
                        'time' => $parts[2],
                        'original_line' => $lineIndex,
                        'replies' => []
                    ];
                    $messageId++;
                }
            }
        }
        
        // 再处理回复
        foreach ($lines as $lineIndex => $line) {
            if (strpos($line, '|reply|') !== false) {
                $parts = explode('|', $line, 5);
                if (count($parts) >= 5 && $parts[1] === 'reply') {
                    $replyToLine = (int)$parts[0];
                    
                    foreach ($messages as &$message) {
                        if ($message['original_line'] == $replyToLine) {
                            $message['replies'][] = [
                                'admin' => $parts[2],
                                'content' => $parts[3],
                                'time' => $parts[4],
                                'id' => uniqid()
                            ];
                            break;
                        }
                    }
                }
            }
        }
        
        // 保存新格式
        if (!empty($messages)) {
            self::writeJsonFile($newFilename, $messages);
        }
        
        // 删除旧文件
        unlink($oldFilename);
    }
    
    /**
     * 渲染错误页面
     */
    public static function renderError($title, $message, $redirectUrl = 'index.php') {
        $title = self::escape($title);
        $message = self::escape($message);
        $redirectUrl = self::escape($redirectUrl);
        
        echo "<!DOCTYPE html>
        <html lang=\"zh-CN\">
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <title>{$title}</title>
            <link rel=\"stylesheet\" href=\"style.css?v=" . time() . "\">
        </head>
        <body>
            <div class=\"error-container\">
                <div class=\"error-card\">
                    <h2>{$title}</h2>
                    <p>{$message}</p>
                    <a href=\"{$redirectUrl}\" class=\"btn btn-primary\">返回留言板</a>
                </div>
            </div>
        </body>
        </html>";
    }
}

// 留言处理类
class MessageHandler {
    
    /**
     * 保存新留言
     */
    public static function saveMessage($name, $message, $clientTime = '') {
        // 验证数据
        $name = Utils::validateLength($name, Config::MAX_NAME_LENGTH, '昵称');
        $message = Utils::validateLength($message, Config::MAX_MESSAGE_LENGTH, '留言内容');
        
        // 规范化换行符 - 将表单提交的 \r\n 统一为 \n
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        
        // 只对昵称进行HTML转义，留言内容保持原始格式在显示时处理
        $name = Utils::escape($name);
        
        // 验证时间
        $time = Utils::validateTime($clientTime);
        
        // 生成文件名
        $filename = date('Ymd') . '.json';
        
        // 读取现有数据
        $messages = Utils::readJsonFile($filename);
        
        // 生成新留言
        $newMessage = [
            'id' => time() . '_' . uniqid(),
            'name' => $name,
            'content' => $message, // 保存规范化后的内容
            'time' => $time,
            'replies' => []
        ];
        
        // 添加到数组开头（最新的在前面）
        array_unshift($messages, $newMessage);
        
        // 保存文件
        Utils::writeJsonFile($filename, $messages);
        
        return true;
    }
    
    /**
     * 保存回复
     */
    public static function saveReply($filename, $messageId, $content, $time = '') {
        // 验证文件
        if (!preg_match(Config::MESSAGE_FILE_PATTERN, basename($filename))) {
            throw new Exception('无效的留言文件');
        }
        
        // 验证内容
        $content = Utils::validateLength($content, Config::MAX_MESSAGE_LENGTH, '回复内容');
        
        // 规范化换行符（保持与 saveMessage 一致）
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        
        // 验证时间
        $time = Utils::validateTime($time);
        
        // 读取现有数据
        $messages = Utils::readJsonFile($filename);
        
        // 查找对应的留言并添加回复
        $found = false;
        foreach ($messages as &$message) {
            if ($message['id'] == $messageId) {
                $message['replies'][] = [
                    'id' => uniqid(),
                    'admin' => 'admin',
                    'content' => $content, // 保存规范化后的内容
                    'time' => $time
                ];
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            throw new Exception('找不到对应的留言');
        }
        
        // 保存文件
        Utils::writeJsonFile($filename, $messages);
        
        return true;
    }
    
    /**
     * 删除留言或回复
     */
    public static function deleteItem($filename, $messageId, $replyId = null) {
        // 验证文件
        if (!preg_match(Config::MESSAGE_FILE_PATTERN, basename($filename))) {
            throw new Exception('无效的留言文件');
        }
        
        // 读取现有数据
        $messages = Utils::readJsonFile($filename);
        
        if ($replyId) {
            // 删除回复
            foreach ($messages as &$message) {
                if ($message['id'] == $messageId) {
                    $message['replies'] = array_filter($message['replies'], function($reply) use ($replyId) {
                        return $reply['id'] !== $replyId;
                    });
                    break;
                }
            }
        } else {
            // 删除整条留言
            $messages = array_filter($messages, function($message) use ($messageId) {
                return $message['id'] !== $messageId;
            });
        }
        
        // 如果没有留言了，删除文件
        if (empty($messages)) {
            if (file_exists($filename)) {
                unlink($filename);
            }
        } else {
            // 重新索引数组并保存
            $messages = array_values($messages);
            Utils::writeJsonFile($filename, $messages);
        }
        
        return true;
    }
    
    /**
     * 获取所有留言
     */
    public static function getAllMessages() {
        $allMessages = [];
        $files = Utils::getMessageFiles();
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            // 检查是否是旧格式文件，如果是则先迁移
            if (preg_match('/\.txt$/', $file)) {
                Utils::migrateOldFormat($file);
                $file = str_replace('.txt', '.json', $file);
            }
            
            $messages = Utils::readJsonFile($file);
            
            foreach ($messages as $message) {
                // 添加文件信息
                $message['file'] = $file;
                $allMessages[] = $message;
            }
        }
        
        // 按时间排序（最新的在前面）
        usort($allMessages, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return $allMessages;
    }
}
?>