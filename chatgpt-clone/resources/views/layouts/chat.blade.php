<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ChatGPT Clone') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            display: flex;
            flex-direction: column;
            height: 100vh;
            color: #333;
        }

        .navbar {
            background-color: #202123;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        .model-select {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background-color: #343541;
            color: white;
            border: 1px solid #565869;
            cursor: pointer;
            width: 300px;
        }

        .user-info {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .container {
            display: flex;
            flex: 1;
            overflow: hidden;
            background-color: #ffffff;
        }

        .sidebar {
            width: 260px;
            background-color: #f5f5f5;
            color: #333333;
            padding: 1rem;
            overflow-y: auto;
        }

        .new-chat-btn {
            width: 100%;
            margin-bottom: 1rem;
            padding: 0.8rem;
            background-color: #19c37d;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .chat-history {
            list-style: none;
        }

        .chat-history li {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
            border-radius: 4px;
        }

        .chat-history li:hover {
            background-color: #e0e0e0;
        }

        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #ffffff;
        }

        .chat-messages {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            color: #333333;
        }

        .message {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 4px;
            max-width: 80%;
        }

        .user-message {
            background-color: #f0f0f0;
            color: #333333;
            margin-left: auto;
        }

        .assistant-message {
            background-color: #e6f7ff;
            color: #333333;
        }

        .message-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .message-actions button {
            padding: 0.3rem 0.6rem;
            font-size: 12px;
            background-color: #f0f0f0;
            color: #333;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .input-container {
            padding: 2rem;
            background-color: #ffffff;
            border-top: 1px solid #dddddd;
        }

        .input-box {
            display: flex;
            gap: 1rem;
            max-width: 1024px;
            margin: 0 auto;
        }

        textarea {
            flex: 1;
            padding: 0.8rem;
            border-radius: 4px;
            border: 1px solid #cccccc;
            background-color: #ffffff;
            color: #333333;
            resize: none;
            min-height: 48px;
            max-height: 200px;
        }

        button {
            padding: 0.8rem 1.5rem;
            background-color: #19c37d;
            color: #493939;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #15a067;
        }

        .footer {
            background-color: #202123;
            color: white;
            padding: 1rem 2rem;
            text-align: center;
            border-top: 1px solid #565869;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-links {
            display: flex;
            gap: 2rem;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
        }

        .file-upload {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .file-preview-item {
            position: relative;
            width: 100px;
            height: 100px;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f9f9f9;
        }

        .file-preview-item span {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: red;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
        }

        .file-preview-item img {
            max-width: 100%;
            max-height: 100%;
        }

        /* File preview styles */
        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .file-preview-item {
            position: relative;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            width: 120px;
            height: 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-color: #f9f9f9;
        }

        .file-preview-item img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .file-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e0e0e0;
            border-radius: 4px;
            margin-top: 15px;
            font-weight: bold;
            color: #555;
        }

        .file-name {
            margin-top: 5px;
            font-size: 12px;
            text-align: center;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            width: 90%;
            padding: 0 5px;
        }

        .remove-file {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }

        /* File upload button */
        .file-upload {
            margin-right: 8px;
        }

        .file-upload button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 5px;
            border-radius: 50%;
        }

        .file-upload button:hover {
            background-color: #f0f0f0;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                max-height: 200px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <form id="modelForm" method="POST" action="{{ route('chats.store') }}">
            @csrf
            <select class="model-select" name="model_option_id"
                onchange="if (this.value === 'new') document.getElementById('modelForm').submit()">
                <option value="new">Select a model</option>
                @foreach ($modelOptions as $option)
                    <option value="{{ $option->id }}" @if (isset($chat) && $chat->model_option_id == $option->id) selected @endif>
                        {{ $option->name }} - {{ $option->description }}
                    </option>
                @endforeach
            </select>
        </form>

        <div class="user-info">
            <span>Welcome, {{ Auth::user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Logout</button>
            </form>
        </div>
    </nav>

    <div class="container">
        <aside class="sidebar">
            <form method="POST" action="{{ route('chats.store') }}">
                @csrf
                @if ($modelOptions->isNotEmpty())
                    <input type="hidden" name="model_option_id" value="{{ $modelOptions->first()->id }}">
                @else
                    <input type="hidden" name="model_option_id" value="">
                    <p>No model options available.</p>
                @endif
                <button type="submit" class="new-chat-btn">New Chat</button>
            </form>

            <ul class="chat-history">
                @foreach ($chats as $chatItem)
                    <li onclick="window.location.href='{{ route('chats.show', $chatItem) }}'">
                        {{ $chatItem->title }}
                    </li>
                @endforeach
            </ul>
        </aside>

        <main class="chat-container">
            @yield('content')
        </main>
    </div>

    <footer class="footer">
        <div class="footer-content">
            <div>© 2024 ChatGPT Clone</div>
            <div class="footer-links">
                <a href="#">Terms</a>
                <a href="#">Privacy</a>
                <a href="#">Contact</a>
            </div>
        </div>
    </footer>
</body>

</html>
