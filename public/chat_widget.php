<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id'])) {
    echo "<!-- Chỉ hiển thị chat cho người đã đăng nhập -->";
    return;
}
$userId = $_SESSION['id'];
?>
<style>
    #chat-box {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 350px;
        background: #f0f2f5;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        font-family: 'Segoe UI', sans-serif;
        overflow: hidden;
        z-index: 9999;
    }

    #chat-header {
        background: #0084ff;
        color: white;
        padding: 12px 16px;
        font-weight: bold;
        cursor: pointer;
        border-top-left-radius: 15px;
        border-top-right-radius: 15px;
    }

    #chat-body {
        background: white;
        display: none;
        flex-direction: column;
    }

    #chat-messages {
        height: 300px;
        overflow-y: auto;
        padding: 15px;
        background: #fff;
    }

    .message {
        max-width: 75%;
        padding: 10px 14px;
        border-radius: 18px;
        margin-bottom: 10px;
        clear: both;
        display: inline-block;
        font-size: 14px;
        line-height: 1.4;
        word-break: break-word;
    }

    .user-message {
        background: #0084ff;
        color: white;
        float: right;
        border-bottom-right-radius: 4px;
    }

    .shop-message {
        background: #e5e5ea;
        color: black;
        float: left;
        border-bottom-left-radius: 4px;
    }

    #chat-input {
        display: flex;
        border-top: 1px solid #ddd;
        background: #f0f2f5;
        padding: 10px;
    }

    #chat-input textarea {
        flex: 1;
        resize: none;
        padding: 10px 12px;
        border-radius: 20px;
        border: none;
        outline: none;
        font-size: 14px;
        background: white;
        font-family: 'Segoe UI', sans-serif;
    }

    #chat-input button {
        background: #0084ff;
        color: white;
        border: none;
        padding: 8px 16px;
        margin-left: 8px;
        border-radius: 20px;
        cursor: pointer;
        transition: background 0.2s;
    }

    #chat-input button:hover {
        background: #006fd6;
    }

    #quick-questions {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 10px;
    }

    .question-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }

    .question-buttons button {
        background-color: #eee;
        border: none;
        border-radius: 20px;
        padding: 6px 14px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .question-buttons button:hover {
        background-color: #ddd;
    }

    #chat-box.minimized {
        width: auto;
        height: auto;
        border-radius: 20px;
        padding: 0;
        background-color: #0084ff;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    #chat-box.minimized #chat-header {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 8px 14px;
        font-size: 14px;
        white-space: nowrap;
        border-radius: 20px;
        background-color: #0084ff;
    }

    #chat-box.minimized #chat-body {
        display: none !important;
    }
</style>

<div id="chat-box" class="minimized">
    <div id="chat-header">💬 Chat với Shop</div>
    <div id="chat-body">
        <div id="quick-questions" style="margin-bottom: 1px;">
            <div class="question-buttons">
                <button onclick="sendQuickQuestion(this)">Có bảo hành bao lâu vậy?</button>
                <button onclick="sendQuickQuestion(this)">Thời gian giao hàng dự kiến?</button>
                <button onclick="sendQuickQuestion(this)">Shop có cửa hàng ở đâu?</button>
                <button onclick="sendQuickQuestion(this)">Có mẫu dưới 2 triệu không?</button>
            </div>
        </div>

        <div id="chat-messages"></div>
        <div id="chat-input">
            <textarea id="message" rows="1" placeholder="Nhập tin nhắn..."></textarea>
            <button onclick="sendMessage()">Gửi</button>
        </div>
    </div>
</div>

<script>
    const chatBox = document.getElementById("chat-box");
    const chatBody = document.getElementById("chat-body");

    document.getElementById("chat-header").onclick = function() {
        const isMinimized = chatBox.classList.contains("minimized");

        if (isMinimized) {
            chatBox.classList.remove("minimized");
            chatBody.style.display = "flex";
        } else {
            chatBox.classList.add("minimized");
            chatBody.style.display = "none";
        }
    };

    // Thu nhỏ khi click ra ngoài
    document.addEventListener("click", function(event) {
        const isClickInside = chatBox.contains(event.target);

        if (!isClickInside) {
            chatBox.classList.add("minimized");
            chatBody.style.display = "none";
        }
    });

    function loadMessages() {
        fetch('chat_get.php')
            .then(res => res.json())
            .then(data => {
                const box = document.getElementById("chat-messages");
                box.innerHTML = '';
                data.forEach(msg => {
                    const div = document.createElement("div");
                    div.classList.add('message');

                    if (msg.sender_role === 'customer') {
                        div.classList.add('user-message');
                        div.textContent = msg.message; // ẩn tên user
                    } else {
                        div.classList.add('shop-message');
                        div.innerHTML = `<strong>Nhân Viên:</strong> ${msg.message}`;
                    }

                    box.appendChild(div);
                });
                if (data.length > 0) {
                    document.getElementById("quick-questions").style.display = "none";
                    firstMessageSent = true;
                }

                box.scrollTop = box.scrollHeight;
            });
    }

    function sendMessage() {
        const msg = document.getElementById("message").value;
        if (msg.trim() === '') return;
        fetch('chat_send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                message: msg
            })
        }).then(() => {
            document.getElementById("message").value = '';
            loadMessages();
        });
    }

    // Gửi bằng Enter, xuống dòng bằng Shift+Enter
    document.getElementById("message").addEventListener("keydown", function(e) {
        if (e.key === "Enter" && !e.shiftKey) {
            e.preventDefault(); // Ngăn xuống dòng nếu không giữ Shift
            sendMessage();
        }
    });

    setInterval(loadMessages, 2000);

    let firstMessageSent = false;

    function sendQuickQuestion(button) {
        if (firstMessageSent) return;

        const text = button.innerText;
        const input = document.getElementById("message");

        input.value = text;

        sendMessage();

        document.getElementById("quick-questions").style.display = "none";
        firstMessageSent = true;
    }
</script>