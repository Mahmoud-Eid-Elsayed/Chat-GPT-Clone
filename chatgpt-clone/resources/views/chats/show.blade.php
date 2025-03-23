@extends('layouts.chat')

@section('content')
    <div class="chat-messages" id="chatBox">
        @if ($chat->messages->isEmpty())
            <div class="message assistant-message">
                Hello! How can I help you today?
            </div>
        @else
            @foreach ($chat->messages as $message)
                <div class="message {{ $message->role === 'user' ? 'user-message' : 'assistant-message' }}"
                    id="message-{{ $message->id }}">
                    {!! nl2br(e($message->content)) !!}

                    @if ($message->file_paths)
                        <div class="file-preview">
                            @foreach ($message->file_paths as $path)
                                <div class="file-preview-item">
                                    <img src="{{ Storage::url($path) }}" alt="Attachment">
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if ($message->role === 'user')
                        <div class="message-actions">
                            <button
                                onclick="editMessage('{{ $message->id }}', '{{ addslashes($message->content) }}')">Edit</button>
                            <form method="POST" action="{{ route('messages.destroy', [$chat, $message]) }}"
                                style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="delete-btn">Delete</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        @endif
    </div>

    <div class="input-container">
        <form id="messageForm" action="{{ route('messages.store', $chat) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <div class="input-box">
                <textarea id="userInput" name="content" placeholder="Type your message here..." rows="1" required></textarea>
                <div class="file-upload">
                    <label for="fileInput">
                        <button type="button" id="image-gen-button" class="p-2 rounded-full hover:bg-gray-200">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>
                    </label>
                    <input id="fileInput" type="file" name="files[]" multiple style="display: none">
                </div>
                <button type="submit">Send</button>

                @if ($chat->messages->isNotEmpty())
                    <button type="button" onclick="regenerateResponse()">Re-generate</button>
                @endif
            </div>
            <div id="filePreview" class="file-preview"></div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chatBox');
            const messageForm = document.getElementById('messageForm');
            const userInput = document.getElementById('userInput');
            const fileInput = document.getElementById('fileInput');
            const filePreview = document.getElementById('filePreview');
            const formMethod = document.getElementById('formMethod');
            const imageGenButton = document.getElementById('image-gen-button');
            let currentMessageId = null;

            // Auto-resize textarea
            userInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 200) + 'px';
            });

            // Scroll to bottom
            chatBox.scrollTop = chatBox.scrollHeight;

            // Handle file input
            fileInput.addEventListener('change', function() {
                filePreview.innerHTML = '';

                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    const reader = new FileReader();

                    const previewItem = document.createElement('div');
                    previewItem.className = 'file-preview-item';

                    reader.onload = function(e) {
                        if (file.type.startsWith('image/')) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            previewItem.appendChild(img);
                        } else {
                            previewItem.textContent = file.name;
                        }
                    };

                    const removeBtn = document.createElement('span');
                    removeBtn.className = 'remove-file';
                    removeBtn.textContent = 'x';
                    removeBtn.onclick = function() {
                        previewItem.remove();
                    };

                    previewItem.appendChild(removeBtn);
                    filePreview.appendChild(previewItem);

                    reader.readAsDataURL(file);
                }
            });

            // Image generation button handler
            imageGenButton.addEventListener('click', function() {
                const prompt = userInput.value.trim();
                const chatId = {{ $chat->id }};

                if (!prompt) {
                    alert('Please enter an image description first');
                    return;
                }

                // Disable the input and button during generation
                userInput.disabled = true;
                this.disabled = true;

                // Send image generation request
                fetch(`/chats/${chatId}/generate-image`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            prompt: prompt
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        // Add generated image to the chat
                        if (data.success && data.imageUrl) {
                            // Create and append message with the generated image
                            const userMessage = document.createElement('div');
                            userMessage.className = 'message user-message';
                            userMessage.innerHTML = `Image prompt: ${prompt}`;

                            const filePreviewDiv = document.createElement('div');
                            filePreviewDiv.className = 'file-preview';

                            const previewItem = document.createElement('div');
                            previewItem.className = 'file-preview-item';

                            const img = document.createElement('img');
                            img.src = data.imageUrl;
                            img.alt = prompt;

                            previewItem.appendChild(img);
                            filePreviewDiv.appendChild(previewItem);
                            userMessage.appendChild(filePreviewDiv);

                            // Add message actions
                            const messageActions = document.createElement('div');
                            messageActions.className = 'message-actions';
                            messageActions.innerHTML = `
                                <button class="delete-btn">Delete</button>
                            `;

                            userMessage.appendChild(messageActions);
                            chatBox.appendChild(userMessage);
                            chatBox.scrollTop = chatBox.scrollHeight;
                        } else {
                            alert('Failed to generate image: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error generating image:', error);
                        alert('Failed to generate image. Please try again.');
                    })
                    .finally(() => {
                        // Re-enable the button and input
                        userInput.disabled = false;
                        this.disabled = false;
                    });
            });

            // Submit form with AJAX
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Check if there's any content
                if (userInput.value.trim() === '' && fileInput.files.length === 0) {
                    return;
                }

                // Create FormData
                const formData = new FormData(this);

                // Add streaming parameter if supported
                const modelSupportsStreaming =
                    {{ $chat->modelOption->supports_streaming ? 'true' : 'false' }};
                if (modelSupportsStreaming) {
                    formData.append('stream', 'true');
                }

                let endpoint = '{{ route('messages.store', $chat) }}';
                let method = 'POST';

                // If editing a message
                if (formMethod.value === 'PUT' && currentMessageId) {
                    endpoint = '{{ url('chats') }}/{{ $chat->id }}/messages/' + currentMessageId;
                    method = 'POST'; // Still using POST with _method=PUT
                    formData.append('_method', 'PUT');
                }

                // Add user message to UI if it's a new message
                if (formMethod.value === 'POST') {
                    const userMessage = document.createElement('div');
                    userMessage.className = 'message user-message';
                    userMessage.innerHTML = userInput.value.replace(/\n/g, '<br>');

                    // Handle file previews
                    if (fileInput.files.length > 0) {
                        const filePreviewDiv = document.createElement('div');
                        filePreviewDiv.className = 'file-preview';

                        for (let i = 0; i < fileInput.files.length; i++) {
                            const file = fileInput.files[i];
                            const previewItem = document.createElement('div');
                            previewItem.className = 'file-preview-item';

                            if (file.type.startsWith('image/')) {
                                const img = document.createElement('img');
                                img.src = URL.createObjectURL(file);
                                previewItem.appendChild(img);
                            } else {
                                previewItem.textContent = file.name;
                            }

                            filePreviewDiv.appendChild(previewItem);
                        }

                        userMessage.appendChild(filePreviewDiv);
                    }

                    // Add message actions
                    const messageActions = document.createElement('div');
                    messageActions.className = 'message-actions';
                    messageActions.innerHTML = `
                        <button onclick="editMessage('temp', '${userInput.value.replace(/'/g, "\\'")}')">Edit</button>
                        <button class="delete-btn">Delete</button>
                    `;

                    userMessage.appendChild(messageActions);
                    chatBox.appendChild(userMessage);

                    // Create placeholder for AI response
                    const aiMessage = document.createElement('div');
                    aiMessage.className = 'message assistant-message';
                    aiMessage.innerHTML = '<div class="typing-indicator">Thinking...</div>';
                    chatBox.appendChild(aiMessage);
                } else {
                    // If editing, update the existing message
                    const messageElement = document.getElementById(`message-${currentMessageId}`);
                    if (messageElement) {
                        // Update the content part of the message
                        messageElement.innerHTML = userInput.value.replace(/\n/g, '<br>');

                        // Add back message actions
                        const messageActions = document.createElement('div');
                        messageActions.className = 'message-actions';
                        messageActions.innerHTML = `
                            <button onclick="editMessage('${currentMessageId}', '${userInput.value.replace(/'/g, "\\'")}')">Edit</button>
                            <form method="POST" action="{{ route('messages.destroy', [$chat, ':id']) }}".replace(':id', ${currentMessageId})
                                style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="delete-btn">Delete</button>
                            </form>
                        `;
                        messageElement.appendChild(messageActions);
                    }
                }

                // Clear input and previews
                userInput.value = '';
                userInput.style.height = 'auto';
                filePreview.innerHTML = '';
                fileInput.value = '';

                // Reset form to POST for new messages
                formMethod.value = 'POST';
                messageForm.action = '{{ route('messages.store', $chat) }}';
                currentMessageId = null;

                // Scroll to bottom
                chatBox.scrollTop = chatBox.scrollHeight;

                // Make AJAX request
                if (modelSupportsStreaming && method === 'POST') {
                    // For streaming, we need to use regular POST and handle streaming differently
                    fetch(endpoint, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => {
                            const reader = response.body.getReader();
                            let decoder = new TextDecoder();
                            let buffer = '';

                            const aiMessage = document.querySelector('.assistant-message:last-child');

                            function processStream({
                                done,
                                value
                            }) {
                                if (done) {
                                    return;
                                }

                                buffer += decoder.decode(value, {
                                    stream: true
                                });

                                // Process complete chunks
                                const lines = buffer.split('\n');
                                buffer = lines.pop(); // Keep the last incomplete line in the buffer

                                for (const line of lines) {
                                    if (line.trim() === '') continue;
                                    if (line.trim() === 'data: [DONE]') return;

                                    try {
                                        if (line.startsWith('data: ')) {
                                            const data = JSON.parse(line.substring(6));
                                            if (data.content) {
                                                aiMessage.innerHTML = data.content.replace(/\n/g,
                                                    '<br>');
                                                chatBox.scrollTop = chatBox.scrollHeight;
                                            }
                                        }
                                    } catch (e) {
                                        console.error('Error parsing JSON:', e, line);
                                    }
                                }

                                // Continue reading
                                return reader.read().then(processStream);
                            }

                            return reader.read().then(processStream);
                        })
                        .catch(error => {
                            const aiMessage = document.querySelector('.assistant-message:last-child');
                            aiMessage.innerHTML = 'Error: Could not get response. Please try again.';
                            console.error('Error:', error);
                        });
                } else {
                    // Handle regular response or edit request
                    fetch(endpoint, {
                            method: method,
                            body: formData,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (method === 'POST' && formMethod.value !== 'PUT') {
                                const aiMessage = document.querySelector(
                                    '.assistant-message:last-child');
                                aiMessage.id = `message-${data.message.id}`;
                                aiMessage.innerHTML = data.message.content.replace(/\n/g, '<br>');
                            } else {
                                // If it was an edit, we already updated the UI
                            }
                            chatBox.scrollTop = chatBox.scrollHeight;
                        })
                        .catch(error => {
                            if (method === 'POST' && formMethod.value !== 'PUT') {
                                const aiMessage = document.querySelector(
                                    '.assistant-message:last-child');
                                aiMessage.innerHTML =
                                    'Error: Could not get response. Please try again.';
                            }
                            console.error('Error:', error);
                        });
                }
            });

            // Add event listener for delete buttons
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('delete-btn') && !e.target.closest('form')) {
                    e.preventDefault();
                    // Handle client-side temporary message deletion
                    const messageElement = e.target.closest('.message');
                    if (messageElement) {
                        messageElement.remove();
                    }
                }
            });
        });

        function editMessage(id, content) {
            const userInput = document.getElementById('userInput');
            const formMethod = document.getElementById('formMethod');
            const messageForm = document.getElementById('messageForm');

            userInput.value = content;
            userInput.focus();
            userInput.dispatchEvent(new Event('input')); // Trigger resize

            // If it's a real message (not a temporary one)
            if (id !== 'temp') {
                formMethod.value = 'PUT';
                window.currentMessageId = id;
                messageForm.action = `{{ url('chats') }}/{{ $chat->id }}/messages/${id}`;
            }
        }

        function regenerateResponse() {
            fetch('{{ route('messages.regenerate', $chat) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (response.ok) {
                        window.location.reload();
                    } else {
                        throw new Error('Failed to regenerate response');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to regenerate response. Please try again.');
                });
        }
    </script>
@endsection
