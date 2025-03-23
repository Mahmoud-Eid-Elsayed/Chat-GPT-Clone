# ERD for ChatGPT Clone System

## Entity Details

### Core Entities:

1. **Users**
   - Primary entity storing user authentication and profile information
   - Has one-to-many relationship with Chats

2. **Chats**
   - Represents chat sessions created by users
   - Links to Users (who created the chat)
   - Links to Model Options (which AI model is being used)
   - Has one-to-many relationship with Messages

3. **Messages**
   - Contains individual messages within chat sessions
   - Messages can be from user or assistant (role field)
   - Can include file attachments

4. **API Providers**
   - Stores information about different AI providers (OpenAI, Ollama, etc.)
   - Has one-to-many relationship with Model Options

5. **Model Options**
   - Contains configuration details for specific AI models
   - Links to API Providers
   - Used by Chats for model selection

### Support Entities:

6. **Password Reset Tokens** - For user password recovery
7. **Sessions** - Tracks user login sessions
8. **Cache** - For application performance optimization


## Key Relationships:

- A User can have multiple Chats
- A Chat belongs to one User
- A Chat uses one Model Option
- A Chat can contain multiple Messages
- An API Provider can offer multiple Model Options
- A Model Option belongs to one API Provider
