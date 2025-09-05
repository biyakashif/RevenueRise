# Task App

A simple command-line task management application built with Node.js.

## Features

- ✅ Add tasks with title, description, and priority
- 📋 List tasks with filtering by status and priority
- ✔️ Mark tasks as completed
- 🗑️ Delete tasks
- 💾 Persistent storage using JSON file
- 🎨 Colorful CLI output
- 🔍 Show detailed task information

## Installation

1. Clone the repository:
```bash
git clone https://github.com/biyakashif12-lgtm/task-app.git
cd task-app
```

2. Install dependencies:
```bash
npm install
```

3. Make the CLI executable (optional):
```bash
npm link
```

## Usage

### Add a new task
```bash
node src/index.js add "Complete project documentation"
node src/index.js add "Fix bug in login" -d "The login form validation is not working" -p high
```

### List tasks
```bash
# List all tasks
node src/index.js list

# List only pending tasks
node src/index.js list -s pending

# List high priority tasks
node src/index.js list -p high
```

### Complete a task
```bash
node src/index.js complete 1
```

### Show task details
```bash
node src/index.js show 1
```

### Delete a task
```bash
node src/index.js delete 1
```

### Get help
```bash
node src/index.js --help
```

## Development

### Run tests
```bash
npm test
```

### Run tests in watch mode
```bash
npm run test:watch
```

### Lint code
```bash
npm run lint
```

### Fix linting issues
```bash
npm run lint:fix
```

### Run in development mode
```bash
npm run dev
```

## Project Structure

```
task-app/
├── src/
│   ├── index.js          # CLI entry point
│   ├── TaskManager.js    # Core task management logic
│   └── utils/
│       └── display.js    # Display utilities
├── tests/
│   └── TaskManager.test.js # Unit tests
├── package.json
├── .eslintrc.js         # ESLint configuration
├── jest.config.js       # Jest configuration
├── .gitignore
└── README.md
```

## Task Properties

Each task has the following properties:

- **id**: Unique identifier (auto-generated)
- **title**: Task title (required)
- **description**: Optional task description
- **priority**: Task priority (low, medium, high) - default: medium
- **status**: Task status (pending, completed) - default: pending
- **createdAt**: Timestamp when task was created
- **updatedAt**: Timestamp when task was last modified

## Data Storage

Tasks are stored in a `tasks.json` file in the current working directory. The file is created automatically when you add your first task.

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License.