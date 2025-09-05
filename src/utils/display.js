const chalk = require('chalk');

function getPriorityColor (priority) {
  switch (priority) {
    case 'high': return chalk.red;
    case 'medium': return chalk.yellow;
    case 'low': return chalk.green;
    default: return chalk.white;
  }
}

function getStatusIcon (status) {
  return status === 'completed' ? chalk.green('✓') : chalk.yellow('○');
}

function formatDate (dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function displayTask (task, detailed = false) {
  const statusIcon = getStatusIcon(task.status);
  const priorityColor = getPriorityColor(task.priority);

  console.log(`${statusIcon} [${task.id}] ${chalk.bold(task.title)}`);

  if (task.description) {
    console.log(`   ${chalk.gray(task.description)}`);
  }

  console.log(`   Priority: ${priorityColor(task.priority.toUpperCase())} | Status: ${task.status}`);

  if (detailed) {
    console.log(`   Created: ${chalk.gray(formatDate(task.createdAt))}`);
    console.log(`   Updated: ${chalk.gray(formatDate(task.updatedAt))}`);
  }
  console.log();
}

function displayTasks (tasks) {
  if (tasks.length === 0) {
    console.log(chalk.yellow('No tasks found.'));
    return;
  }

  console.log(chalk.bold(`\nFound ${tasks.length} task(s):\n`));

  // Sort tasks by priority and then by creation date
  const priorityOrder = { high: 3, medium: 2, low: 1 };
  const sortedTasks = tasks.sort((a, b) => {
    const priorityDiff = priorityOrder[b.priority] - priorityOrder[a.priority];
    if (priorityDiff !== 0) return priorityDiff;
    return new Date(a.createdAt) - new Date(b.createdAt);
  });

  sortedTasks.forEach(task => displayTask(task));
}

function displaySummary (taskManager) {
  const counts = taskManager.getTaskCount();
  console.log(chalk.bold('\nTask Summary:'));
  console.log(`Total: ${counts.total}`);
  console.log(`Pending: ${chalk.yellow(counts.pending)}`);
  console.log(`Completed: ${chalk.green(counts.completed)}`);
}

module.exports = {
  displayTask,
  displayTasks,
  displaySummary,
  getPriorityColor,
  getStatusIcon,
  formatDate
};
