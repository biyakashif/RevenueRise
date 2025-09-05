const TaskManager = require('../src/TaskManager');
const fs = require('fs');
const path = require('path');

describe('TaskManager', () => {
  let taskManager;
  const testDataFile = 'test-tasks.json';

  beforeEach(() => {
    // Clean up any existing test file
    if (fs.existsSync(testDataFile)) {
      fs.unlinkSync(testDataFile);
    }
    taskManager = new TaskManager(testDataFile);
  });

  afterEach(() => {
    // Clean up test file
    if (fs.existsSync(testDataFile)) {
      fs.unlinkSync(testDataFile);
    }
  });

  describe('addTask', () => {
    it('should add a new task with valid data', () => {
      const task = taskManager.addTask('Test Task', 'Test description', 'high');
      
      expect(task).toBeDefined();
      expect(task.id).toBe(1);
      expect(task.title).toBe('Test Task');
      expect(task.description).toBe('Test description');
      expect(task.priority).toBe('high');
      expect(task.status).toBe('pending');
      expect(task.createdAt).toBeDefined();
      expect(task.updatedAt).toBeDefined();
    });

    it('should throw error for empty title', () => {
      expect(() => {
        taskManager.addTask('');
      }).toThrow('Task title is required');
    });

    it('should throw error for invalid priority', () => {
      expect(() => {
        taskManager.addTask('Test Task', 'Description', 'invalid');
      }).toThrow('Priority must be one of: low, medium, high');
    });

    it('should use default priority when not specified', () => {
      const task = taskManager.addTask('Test Task');
      expect(task.priority).toBe('medium');
    });

    it('should assign incremental IDs', () => {
      const task1 = taskManager.addTask('Task 1');
      const task2 = taskManager.addTask('Task 2');
      
      expect(task1.id).toBe(1);
      expect(task2.id).toBe(2);
    });
  });

  describe('getTasks', () => {
    beforeEach(() => {
      taskManager.addTask('Task 1', 'Description 1', 'high');
      taskManager.addTask('Task 2', 'Description 2', 'low');
      taskManager.completeTask(1);
    });

    it('should return all tasks when no filters applied', () => {
      const tasks = taskManager.getTasks();
      expect(tasks).toHaveLength(2);
    });

    it('should filter tasks by status', () => {
      const pendingTasks = taskManager.getTasks('pending');
      const completedTasks = taskManager.getTasks('completed');
      
      expect(pendingTasks).toHaveLength(1);
      expect(completedTasks).toHaveLength(1);
      expect(pendingTasks[0].title).toBe('Task 2');
      expect(completedTasks[0].title).toBe('Task 1');
    });

    it('should filter tasks by priority', () => {
      const highPriorityTasks = taskManager.getTasks(null, 'high');
      const lowPriorityTasks = taskManager.getTasks(null, 'low');
      
      expect(highPriorityTasks).toHaveLength(1);
      expect(lowPriorityTasks).toHaveLength(1);
    });

    it('should throw error for invalid status filter', () => {
      expect(() => {
        taskManager.getTasks('invalid');
      }).toThrow('Status must be either "pending" or "completed"');
    });
  });

  describe('getTask', () => {
    it('should return task by ID', () => {
      const addedTask = taskManager.addTask('Test Task');
      const retrievedTask = taskManager.getTask(addedTask.id);
      
      expect(retrievedTask).toEqual(addedTask);
    });

    it('should throw error for non-existent task', () => {
      expect(() => {
        taskManager.getTask(999);
      }).toThrow('Task with ID 999 not found');
    });
  });

  describe('completeTask', () => {
    it('should mark task as completed', () => {
      const task = taskManager.addTask('Test Task');
      const completedTask = taskManager.completeTask(task.id);
      
      expect(completedTask.status).toBe('completed');
      expect(completedTask.updatedAt).toBeDefined();
      expect(completedTask.id).toBe(task.id);
    });

    it('should throw error for already completed task', () => {
      const task = taskManager.addTask('Test Task');
      taskManager.completeTask(task.id);
      
      expect(() => {
        taskManager.completeTask(task.id);
      }).toThrow('Task is already completed');
    });
  });

  describe('deleteTask', () => {
    it('should delete existing task', () => {
      const task = taskManager.addTask('Test Task');
      const result = taskManager.deleteTask(task.id);
      
      expect(result).toBe(true);
      expect(() => {
        taskManager.getTask(task.id);
      }).toThrow();
    });

    it('should throw error for non-existent task', () => {
      expect(() => {
        taskManager.deleteTask(999);
      }).toThrow('Task with ID 999 not found');
    });
  });

  describe('getTaskCount', () => {
    it('should return correct task counts', () => {
      taskManager.addTask('Task 1');
      taskManager.addTask('Task 2');
      taskManager.completeTask(1);
      
      const counts = taskManager.getTaskCount();
      
      expect(counts.total).toBe(2);
      expect(counts.pending).toBe(1);
      expect(counts.completed).toBe(1);
    });
  });

  describe('persistence', () => {
    it('should save and load tasks from file', () => {
      taskManager.addTask('Persistent Task');
      
      // Create new instance to test loading
      const newTaskManager = new TaskManager(testDataFile);
      const tasks = newTaskManager.getTasks();
      
      expect(tasks).toHaveLength(1);
      expect(tasks[0].title).toBe('Persistent Task');
    });
  });
});