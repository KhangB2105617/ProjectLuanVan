<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../src/bootstrap.php';

// Kiểm tra quyền admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: /unauthorized.php");
    exit;
}
$pageTitle = "Tin nhắn người dùng";
include 'includes/header.php';
?>
<div class="container-fluid">
    <div class="row">
        <?php include 'includes/sidebar.php'; ?> <!-- ✅ Thêm sidebar vào đây -->

        <main class="col-md-10 ms-sm-auto px-md-4" style="margin-left: 17%;">
    <div class="pt-4">
        <h1><?= $pageTitle ?></h1>
        
        <!-- 👉 Thêm lớp chat-container mới để gom layout -->
        <div class="chat-container" style="display: flex; height: calc(100vh - 130px);">
            
            <!-- ✅ Danh sách người dùng -->
            <div class="sidebar" id="userList"></div>

            <!-- ✅ Khung chat -->
            <div class="chat-area">
                <div class="chat-header" id="chatHeader">Chọn người để bắt đầu</div>
                <div class="messages" id="chatMessages"></div>
                <div class="message-input">
                    <textarea id="adminMessage" placeholder="Nhập tin nhắn..."></textarea>
                    <button onclick="sendMessage()">Gửi</button>
                </div>
            </div>
        </div>
    </div>
</main>
    </div>
</div>
<style>
   body {
    margin: 0;
    height: 100vh;
    font-family: 'Segoe UI', sans-serif;
    background-color: #f0f2f5;
}

.sidebar {
    width: 25%;
    height: 100vh;
    background: white;
    overflow-y: auto;
    border-right: 1px solid #ddd;
    box-shadow: 2px 0 5px rgba(0,0,0,0.05);
}

.user-item {
    padding: 15px 20px;
    cursor: pointer;
    border-bottom: 1px solid #f1f1f1;
    transition: background 0.2s;
}

.user-item:hover {
    background: #f0f2f5;
    font-weight: 500;
}

.chat-area {
    width: 75%;
    height: 100vh;
    display: flex;
    flex-direction: column;
    background: #fff;
}

.chat-header {
    padding: 15px 20px;
    background: #0084ff;
    color: white;
    font-weight: bold;
    font-size: 18px;
    border-bottom: 1px solid #0070d1;
}

.messages {
    flex-grow: 1;
    padding: 20px;
    overflow-y: auto;
    background: #e5ddd5;
    display: flex;
    flex-direction: column;
}

.message {
    max-width: 60%;
    padding: 10px 15px;
    border-radius: 20px;
    margin-bottom: 12px;
    font-size: 14px;
    line-height: 1.4;
    word-wrap: break-word;
    position: relative;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.message.admin {
    background-color: #0084ff;
    color: white;
    align-self: flex-end;
    border-bottom-right-radius: 4px;
    text-align: left;
}

.message.user {
    background-color: #fff;
    color: #000;
    align-self: flex-start;
    border-bottom-left-radius: 4px;
}

.message small {
    display: block;
    font-size: 11px;
    color: #999;
    margin-top: 5px;
    text-align: right;
}

.message-input {
    display: flex;
    padding: 12px 15px;
    border-top: 1px solid #ccc;
    background: #f0f2f5;
    align-items: center;
}

.message-input textarea {
    flex: 1;
    resize: none;
    padding: 10px 12px;
    border-radius: 20px;
    border: none;
    font-size: 14px;
    outline: none;
    font-family: 'Segoe UI', sans-serif;
    background: white;
    margin-right: 10px;
    height: 38px;
}

.message-input button {
    background: #0084ff;
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 20px;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
}

.message-input button:hover {
    background: #006fd6;
}

</style>
</head>

<body>

    <script>
        let currentUserId = null;

        function loadUsers() {
            fetch('get_chat_users.php')
                .then(res => res.json())
                .then(users => {
                    const userList = document.getElementById('userList');
                    userList.innerHTML = '';
                    users.forEach(user => {
                        const div = document.createElement('div');
                        div.className = 'user-item';
                        div.textContent = user.username;
                        div.onclick = () => {
                            currentUserId = user.id;
                            document.getElementById('chatHeader').textContent = 'Đang chat với: ' + user.username;
                            loadMessages();
                        };
                        userList.appendChild(div);
                    });
                });
        }

        function loadMessages() {
            if (!currentUserId) return;
            fetch('get_chat_messages.php?user_id=' + currentUserId)
                .then(res => res.json())
                .then(messages => {
                    const chatBox = document.getElementById('chatMessages');
                    chatBox.innerHTML = '';
                    messages.forEach(msg => {
                        const div = document.createElement('div');
                        div.className = 'message ' + (msg.sender === 'admin' ? 'admin' : 'user');
                        div.innerHTML = `${msg.message}<small>${msg.created_at}</small>`;
                        chatBox.appendChild(div);

                    });
                    chatBox.scrollTop = chatBox.scrollHeight;
                });
        }

        function sendMessage() {
            const message = document.getElementById('adminMessage').value;
            if (!message || !currentUserId) return;
            fetch('send_admin_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: currentUserId,
                        sender: 'admin',
                        message
                    })
                })
                .then(res => res.json())
                .then(() => {
                    document.getElementById('adminMessage').value = '';
                    loadMessages();
                });
        }

        setInterval(() => {
            if (currentUserId) loadMessages();
        }, 3000); // tự động refresh tin nhắn mỗi 3s

        loadUsers();
    </script>

</body>

</html>