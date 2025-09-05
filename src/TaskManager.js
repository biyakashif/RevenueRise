const fs = require('fs');
const path = require('path');

class TaskManager {
  constructor (dataFile = 'tasks.json') {
    this.dataFile = path.join(process.cwd(), dataFile);
    this.tasks = this.loadTasks();
  }

  loadTasks () {
    try {
      if (fs.existsSync(this.dataFile)) {
        const data = fs.readFileSync(this.dataFile, 'utf8');
        return JSON.parse(data);
      }
      return [];
    } catch (error) {
      console.error('Error loading tasks:', error.message);
      return [];
    }
  }

  saveTasks () {
    try {
      fs.writeFileSync(this.dataFile, JSON.stringify(this.tasks, null, 2));
    } catch (error) {
      throw new Error(`Failed to save tasks: ${error.message}`);
    }
  }

  addTask (title, description = '', priority = 'medium') {
    if (!title || title.trim() === '') {
      throw new Error('Task title is required');
    }

    const validPriorities = ['low', 'medium', 'high'];
    if (!validPriorities.includes(priority.toLowerCase())) {
      throw new Error('Priority must be one of: low, medium, high');
    }

    const task = {
      id: this.getNextId(),
      title: title.trim(),
      description: description.trim(),
      priority: priority.toLowerCase(),
      status: 'pending',
      createdAt: new Date().toISOString(),
      updatedAt: new Date().toISOString()
    };

    this.tasks.push(task);
    this.saveTasks();
    return task;
  }

  getTasks (status = null, priority = null) {
    let filteredTasks = [...this.tasks];

    if (status) {
      const validStatuses = ['pending', 'completed'];
      if (!validStatuses.includes(status.toLowerCase())) {
        throw new Error('Status must be either "pending" or "completed"');
      }
      filteredTasks = filteredTasks.filter(task => task.status === status.toLowerCase());
    }

    if (priority) {
      const validPriorities = ['low', 'medium', 'high'];
      if (!validPriorities.includes(priority.toLowerCase())) {
        throw new Error('Priority must be one of: low, medium, high');
      }
      filteredTasks = filteredTasks.filter(task => task.priority === priority.toLowerCase());
    }

    return filteredTasks;
  }

  getTask (id) {
    const task = this.tasks.find(task => task.id === id);
    if (!task) {
      throw new Error(`Task with ID ${id} not found`);
    }
    return task;
  }

  completeTask (id) {
    const task = this.getTask(id);
    if (task.status === 'completed') {
      throw new Error('Task is already completed');
    }

    task.status = 'completed';
    task.updatedAt = new Date().toISOString();
    this.saveTasks();
    return task;
  }

  deleteTask (id) {
    const taskIndex = this.tasks.findIndex(task => task.id === id);
    if (taskIndex === -1) {
      throw new Error(`Task with ID ${id} not found`);
    }

    this.tasks.splice(taskIndex, 1);
    this.saveTasks();
    return true;
  }

  getNextId () {
    if (this.tasks.length === 0) {
      return 1;
    }
    return Math.max(...this.tasks.map(task => task.id)) + 1;
  }

  clearAllTasks () {
    this.tasks = [];
    this.saveTasks();
  }

  getTaskCount () {
    return {
      total: this.tasks.length,
      pending: this.tasks.filter(task => task.status === 'pending').length,
      completed: this.tasks.filter(task => task.status === 'completed').length
    };
  }
}

module.exports = TaskManager;
