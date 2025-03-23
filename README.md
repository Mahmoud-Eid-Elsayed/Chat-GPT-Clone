# ChatGPT Clone

A full-featured, self-hosted chat application powered by AI, supporting multiple language models and providers.

## 🌟 Features

- 💬 Real-time chat interface with streaming responses
- 🔄 Support for multiple AI providers (OpenAI)
- 🧠 Multiple model options with different capabilities
- 📁 File attachments and image uploads
- 🖼️ Image generation using DALL-E
- 📱 Responsive design for mobile and desktop
- 🔒 User authentication and conversation history
- 🌐 Self-hosted and privately deployed

## 📋 Requirements

- PHP
- MySQL/PostgreSQL
- Composer
- Node.js and NPM
- Laravel 10+
- API keys for providers (OpenAI)
- Optional: Ollama for local models

## 🚀 Installation

1. Clone the repository
```bash
git clone https://github.com/Mahmoud-Eid-Elsayed/Chat-GPT-Clone
cd chatgpt-clone
```

2. Install PHP dependencies
```bash
composer install
```

3. Install JavaScript dependencies
```bash
npm install
```

4. Copy the environment file and configure your settings
```bash
cp .env.example .env
```

5. Generate application key
```bash
php artisan key:generate
```

6. Configure your database in the `.env` file
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chatgpt_clone
DB_USERNAME=root
DB_PASSWORD=
```

7. Configure your AI provider API keys
```
OPENAI_API_KEY=your-api-key
OLLAMA_API_URL=http://localhost:11434
```

8. Run migrations and seed the database
```bash
php artisan migrate --seed
```

9. Build assets
```bash
npm run build
```

10. Start the development server
```bash
php artisan serve
```

11. Visit `http://localhost:8000` in your browser

## 🧩 Configuration

### AI Providers

The application supports multiple AI providers:

#### OpenAI
- Set your API key in the `.env` file as `OPENAI_API_KEY`
- Supports GPT models, DALL-E for image generation, Whisper for speech-to-text, and TTS

#### Ollama
- Install Ollama on your local machine or server
- Set the API URL in the `.env` file (default: `http://localhost:11434`)
- Download models using Ollama CLI: `ollama pull llama3` or `ollama pull mistral`

### Adding New Models

1. Go to the admin panel
2. Navigate to the "Models" section
3. Click "Add New Model"
4. Fill in the details for the model

## 💻 Usage

1. Register a new account or log in
2. Create a new chat by selecting a model
3. Start sending messages
4. Upload files as needed
5. View and manage your chat history

## 🛠️ Development

To contribute to the development:

```bash
# Run development server
php artisan serve

# Watch for asset changes
npm run dev

# Run tests
php artisan test
```

## 📝 License

This project is licensed under the custom license - see the LICENSE file for details.

## 👨‍💻 Author

**Mahmoud Elsayed**

## 🙏 Acknowledgements

- Laravel Team for the amazing framework
- OpenAI for their API
