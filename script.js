const users = [
  { id: 1, name: 'Alice', avatar: '', online: true },
  { id: 2, name: 'Bob', avatar: '', online: false },
  { id: 3, name: 'Charlie', avatar: '', online: true },
];

let selectedUser = null;

document.addEventListener('DOMContentLoaded', () => {
  const userList = document.getElementById('userList');
  const chatHeader = document.getElementById('chatHeader');
  const chatMessages = document.getElementById('chatMessages');
  const messageForm = document.getElementById('messageForm');
  const messageInput = document.getElementById('messageInput');

  users.forEach(user => {
    const userItem = document.createElement('div');
    userItem.classList.add('user-item');
    userItem.innerHTML = `
      <div class="user-avatar"></div>
      <div>${user.name}</div>
    `;
    userItem.addEventListener('click', () => {
      selectedUser = user;
      chatHeader.textContent = user.name;
      chatMessages.innerHTML = '';
    });
    userList.appendChild(userItem);
  });

  messageForm.addEventListener('submit', e => {
    e.preventDefault();
    if (!selectedUser) return;

    const messageText = messageInput.value.trim();
    if (messageText === '') return;

    const messageElement = document.createElement('div');
    messageElement.classList.add('message', 'sent');
    messageElement.textContent = messageText;
    chatMessages.appendChild(messageElement);
    messageInput.value = '';
    chatMessages.scrollTop = chatMessages.scrollHeight;

    setTimeout(() => {
      const replyElement = document.createElement('div');
      replyElement.classList.add('message', 'received');
      replyElement.textContent = `Reply from ${selectedUser.name}`;
      chatMessages.appendChild(replyElement);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 1000);
  });
});
