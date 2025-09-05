#!/usr/bin/env node

const { Command } = require('commander');
const TaskManager = require('./TaskManager');
const { displayTasks, displayTask } = require('./utils/display');
const chalk = require('chalk');

const program = new Command();
const taskManager = new TaskManager();

program
  .name('task-app')
  .description('A simple task management CLI application')
  .version('1.0.0');

program
  .command('add <title>')
  .description('Add a new task')
  .option('-d, --description <desc>', 'Task description')
  .option('-p, --priority <priority>', 'Task priority (low, medium, high)', 'medium')
  .action((title, options) => {
    try {
      const task = taskManager.addTask(title, options.description, options.priority);
      console.log(chalk.green('✓ Task added successfully:'));
      displayTask(task);
    } catch (error) {
      console.error(chalk.red('Error:', error.message));
    }
  });

program
  .command('list')
  .description('List all tasks')
  .option('-s, --status <status>', 'Filter by status (pending, completed)')
  .option('-p, --priority <priority>', 'Filter by priority (low, medium, high)')
  .action((options) => {
    try {
      const tasks = taskManager.getTasks(options.status, options.priority);
      if (tasks.length === 0) {
        console.log(chalk.yellow('No tasks found.'));
        return;
      }
      displayTasks(tasks);
    } catch (error) {
      console.error(chalk.red('Error:', error.message));
    }
  });

program
  .command('complete <id>')
  .description('Mark a task as completed')
  .action((id) => {
    try {
      const task = taskManager.completeTask(parseInt(id));
      console.log(chalk.green('✓ Task marked as completed:'));
      displayTask(task);
    } catch (error) {
      console.error(chalk.red('Error:', error.message));
    }
  });

program
  .command('delete <id>')
  .description('Delete a task')
  .action((id) => {
    try {
      taskManager.deleteTask(parseInt(id));
      console.log(chalk.green('✓ Task deleted successfully.'));
    } catch (error) {
      console.error(chalk.red('Error:', error.message));
    }
  });

program
  .command('show <id>')
  .description('Show details of a specific task')
  .action((id) => {
    try {
      const task = taskManager.getTask(parseInt(id));
      displayTask(task, true);
    } catch (error) {
      console.error(chalk.red('Error:', error.message));
    }
  });

// Handle unknown commands
program.on('command:*', () => {
  console.error(chalk.red('Invalid command. Use --help to see available commands.'));
  process.exit(1);
});

// Parse command line arguments
program.parse();

// Show help if no command provided
if (!process.argv.slice(2).length) {
  program.outputHelp();
}
