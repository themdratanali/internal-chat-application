<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Internal Chat</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/chat.css">
</head>
<body>

<div class="sidebar">
    <h4>My Conversation</h4>
    <input type="text" id="searchInput" placeholder="Search users..." onkeyup="searchUsers()" style="width:100%; padding:10px; margin-bottom:10px; border-radius:8px; border:1px solid #ccc;">
    <div id="searchResults" style="margin-top:10px;"></div>
    <div id="user-list"></div>
</div>

<div class="chat-container">
    <div class="top-bar">
        <div>Welcome, <?php echo $_SESSION['username']; ?></div>
        <a class="profile" href="edit_profile.php">Profile</a>
        <a class="logout" href="logout.php">Logout</a>        
    </div>

    <div class="chat-header" style="display: none;" id="chat-header">
        <div class="chat-header-left">
            <img src="default-photo.jpg" alt="Receiver Photo" class="receiver-photo" id="receiver-photo">
            <span class="receiver-name" id="chat-with-name">Select a user to chat</span>
        </div>
        <div class="chat-header-right">
            <button class="call-icon">
                <img src="assets/photo/audio-call-icon.png" alt="Audio Call">
            </button>
            <button class="call-icon">
                <img src="assets/photo/video-call-icon.png" alt="Video Call">
            </button>
        </div>
    </div>

    <div id="chat-messages"></div>

    <form id="chat-form">
        <input type="hidden" id="receiver_id">
        <input type="text" id="message" placeholder="Type message..." required>
        <button type="submit">Send</button>
    </form>
</div>

<script>
let currentReceiver = null;

function loadUsers() {
    fetch('get_users.php')
        .then(res => res.text())
        .then(data => {
            document.getElementById('user-list').innerHTML = data;
    });
}

function selectUser(id, name, photo) {
    currentReceiver = id;
    document.getElementById('receiver_id').value = id;
    document.getElementById('chat-with-name').innerText = name;
    const receiverPhoto = document.querySelector('.receiver-photo');
    receiverPhoto.src = photo || 'default-photo.jpg';
    document.getElementById('chat-header').style.display = 'flex';
    loadMessages();
}

function loadMessages() {
    fetch('get_messages.php?receiver_id=' + currentReceiver)
        .then(res => res.text())
        .then(data => {
            const chatBox = document.getElementById('chat-messages');
            chatBox.innerHTML = data;
            chatBox.scrollTop = chatBox.scrollHeight;
        });
}

document.getElementById('chat-form').addEventListener('submit', function (e) {
    e.preventDefault();
    const receiver = document.getElementById('receiver_id').value;
    const message = document.getElementById('message').value;

    fetch('send_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'receiver_id=' + receiver + '&message=' + encodeURIComponent(message)
    }).then(() => {
        document.getElementById('message').value = '';
        loadMessages();
        loadUsers();
    });
});

setInterval(() => {
    if (currentReceiver) loadMessages();
}, 2000);

loadUsers();
</script>

<script>
let searchTimeout;

function searchUsers() {
    clearTimeout(searchTimeout);

    searchTimeout = setTimeout(() => {
        const query = document.getElementById('searchInput').value.trim();

        if (query.length === 0) {
            document.getElementById('searchResults').innerHTML = '';
            return;
        }

        fetch(`search_users.php?query=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(users => {
                let output = '';
                if (users.length === 0) {
                    output = '<p>No users found.</p>';
                } else {
                    users.forEach(user => {
                        const safeName = user.name.replace(/'/g, "\\'");
                        const safePhoto = user.photo ? user.photo.replace(/'/g, "\\'") : 'default-photo.jpg';

                        output += `
                            <div onclick="selectUser(${user.id}, '${safeName}', '${safePhoto}')" style="padding:10px; border-bottom:1px solid #eee; cursor:pointer;">
                                <img src="${safePhoto}" style="width:30px; height:30px; border-radius:50%; vertical-align:middle;">
                                <span style="margin-left:10px;">${user.name} (${user.username})</span>
                            </div>
                        `;
                    });
                }
                document.getElementById('searchResults').innerHTML = output;
            });
    }, 300);
}
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById('chat-header').style.display = 'none';
    });
</script>

</body>
</html>
