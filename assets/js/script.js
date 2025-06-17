document.addEventListener('DOMContentLoaded', function() {
    // Elemen UI penting
    const chatForm = document.getElementById('chatForm');
    const chatBox = document.getElementById('chatBox');
    const messageInput = chatForm.querySelector('input[name="message"]');
    const fileUploadForm = document.querySelector('form[enctype="multipart/form-data"]');
    const fileInput = fileUploadForm.querySelector('input[type="file"]');
    
    // Auto-scroll ke bawah chat
    function scrollToBottom() {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
    
    // Tampilkan pesan baru di chat box
    function appendMessage(role, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${role}-message`;
        
        messageDiv.innerHTML = `
            <div class="message-header">
                <strong>${role.charAt(0).toUpperCase() + role.slice(1)}</strong>
            </div>
            <div class="message-content">
                ${content.replace(/\n/g, '<br>')}
            </div>
        `;
        
        chatBox.appendChild(messageDiv);
        scrollToBottom();
    }
    
    // Handle pengiriman pesan
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) return;
        
        // Tampilkan pesan pengguna
        appendMessage('user', message);
        
        // Buat elemen loading
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'message bot-message loading-message';
        loadingDiv.innerHTML = `
            <div class="message-header">
                <strong>DeepSeek</strong>
            </div>
            <div class="message-content">
                <div class="spinner-border spinner-border-sm" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                Thinking...
            </div>
        `;
        chatBox.appendChild(loadingDiv);
        scrollToBottom();
        
        // Reset input
        messageInput.value = '';
        messageInput.focus();
        
        try {
            // Kirim permintaan ke server
            const formData = new FormData(chatForm);
            const response = await fetch('api/chat.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            // Hapus loading dan tampilkan respons
            chatBox.removeChild(loadingDiv);
            
            if (data.error) {
                appendMessage('bot', `Error: ${data.error}`);
            } else {
                appendMessage('bot', data.response);
            }
        } catch (error) {
            console.error('Error:', error);
            chatBox.removeChild(loadingDiv);
            appendMessage('bot', `Sorry, something went wrong. Please try again. Error: ${error.message}`);
        }
    });
    
    // Handle file upload
    fileUploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!fileInput.files || fileInput.files.length === 0) {
            alert('Please select a file first');
            return;
        }
        
        const submitBtn = fileUploadForm.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        
        // Tampilkan loading
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            Uploading...
        `;
        
        try {
            const formData = new FormData(fileUploadForm);
            const response = await fetch('api/upload.php', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.error) {
                alert(`Upload failed: ${data.error}`);
            } else {
                // Refresh daftar file
                refreshFileList();
                alert('File uploaded successfully!');
                fileInput.value = '';
            }
        } catch (error) {
            console.error('Upload error:', error);
            alert(`Upload failed: ${error.message}`);
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    });
    
    // Refresh daftar file
    async function refreshFileList() {
        try {
            const response = await fetch('api/get_files.php');
            const files = await response.json();
            
            const fileListContainer = document.querySelector('.file-list');
            if (files.length === 0) {
                fileListContainer.innerHTML = '<p class="text-muted">No files uploaded yet.</p>';
                return;
            }
            
            let html = '<ul class="list-group">';
            files.forEach(file => {
                html += `
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>${escapeHtml(file.file_name)}</span>
                        <small class="text-muted">${formatDate(file.uploaded_at)}</small>
                    </li>
                `;
            });
            html += '</ul>';
            
            fileListContainer.innerHTML = html;
        } catch (error) {
            console.error('Error refreshing file list:', error);
        }
    }
    
    // Format tanggal
    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'short', day: 'numeric' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }
    
    // Escape HTML untuk keamanan
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
    
    // Auto-resize textarea (jika menggunakan textarea)
    function adjustTextareaHeight(textarea) {
        textarea.style.height = 'auto';
        textarea.style.height = (textarea.scrollHeight) + 'px';
    }
    
    // Inisialisasi
    scrollToBottom();
    
    // Jika menggunakan textarea bukan input
    if (messageInput.tagName === 'TEXTAREA') {
        messageInput.addEventListener('input', function() {
            adjustTextareaHeight(this);
        });
        adjustTextareaHeight(messageInput);
    }
    
    // Hotkey: Shift+Enter untuk new line, Enter untuk kirim
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });
});
