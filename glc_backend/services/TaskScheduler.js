const cron = require('node-cron');
const fs = require('fs');
const path = require('path');
const { exec } = require('child_process');

class TaskScheduler {
    constructor() {
        this.tasks = new Map();
        this.setupDefaultTasks();
    }

    setupDefaultTasks() {
        // Sermon refresh - every day at 6 AM
        this.scheduleTask('sermon-refresh', '0 6 * * *', () => {
            console.log('🎥 Running scheduled sermon refresh...');
            this.refreshSermons();
        });

        // Database backup - every Sunday at 2 AM
        this.scheduleTask('database-backup', '0 2 * * 0', () => {
            console.log('💾 Running scheduled database backup...');
            this.backupDatabase();
        });

        // Cleanup old uploads - every Monday at 3 AM
        this.scheduleTask('cleanup-uploads', '0 3 * * 1', () => {
            console.log('🧹 Running scheduled cleanup...');
            this.cleanupOldUploads();
        });

        // Health check - every hour
        this.scheduleTask('health-check', '0 * * * *', () => {
            console.log('❤️ Running health check...');
            this.performHealthCheck();
        });
    }

    scheduleTask(name, cronPattern, taskFunction) {
        if (this.tasks.has(name)) {
            console.log(`⚠️ Task ${name} already exists, replacing...`);
            this.tasks.get(name).destroy();
        }

        const task = cron.schedule(cronPattern, taskFunction, {
            scheduled: true,
            timezone: "America/New_York" // Adjust to your timezone
        });

        this.tasks.set(name, task);
        console.log(`✅ Scheduled task: ${name} (${cronPattern})`);
    }

    async refreshSermons() {
        try {
            // Run your existing sermon refresh script
            exec('node refresh_sermons.js', { cwd: path.join(__dirname, '..') }, (error, stdout, stderr) => {
                if (error) {
                    console.error('❌ Sermon refresh failed:', error);
                } else {
                    console.log('✅ Sermon refresh completed:', stdout);
                }
            });
        } catch (error) {
            console.error('❌ Sermon refresh error:', error);
        }
    }

    async backupDatabase() {
        try {
            const dbPath = path.join(__dirname, 'liberty_church.db');
            const backupDir = path.join(__dirname, '..', 'backups');
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
            const backupPath = path.join(backupDir, `liberty_church_backup_${timestamp}.db`);

            // Create backup directory if it doesn't exist
            if (!fs.existsSync(backupDir)) {
                fs.mkdirSync(backupDir, { recursive: true });
            }

            // Copy database file
            fs.copyFileSync(dbPath, backupPath);
            
            // Keep only last 7 backups
            this.cleanupOldBackups(backupDir);
            
            console.log(`✅ Database backed up to: ${backupPath}`);
        } catch (error) {
            console.error('❌ Database backup failed:', error);
        }
    }

    async cleanupOldUploads() {
        try {
            const uploadsDir = path.join(__dirname, '..', 'uploads');
            const thirtyDaysAgo = Date.now() - (30 * 24 * 60 * 60 * 1000);

            if (!fs.existsSync(uploadsDir)) return;

            const files = fs.readdirSync(uploadsDir);
            let cleanedCount = 0;

            for (const file of files) {
                const filePath = path.join(uploadsDir, file);
                const stats = fs.statSync(filePath);
                
                if (stats.mtime.getTime() < thirtyDaysAgo) {
                    fs.unlinkSync(filePath);
                    cleanedCount++;
                }
            }

            console.log(`✅ Cleaned up ${cleanedCount} old upload files`);
        } catch (error) {
            console.error('❌ Upload cleanup failed:', error);
        }
    }

    cleanupOldBackups(backupDir) {
        try {
            const files = fs.readdirSync(backupDir)
                .filter(file => file.startsWith('liberty_church_backup_'))
                .map(file => ({
                    name: file,
                    path: path.join(backupDir, file),
                    time: fs.statSync(path.join(backupDir, file)).mtime.getTime()
                }))
                .sort((a, b) => b.time - a.time);

            // Keep only the 7 most recent backups
            if (files.length > 7) {
                const toDelete = files.slice(7);
                toDelete.forEach(file => {
                    fs.unlinkSync(file.path);
                    console.log(`🗑️ Deleted old backup: ${file.name}`);
                });
            }
        } catch (error) {
            console.error('❌ Backup cleanup failed:', error);
        }
    }

    performHealthCheck() {
        try {
            // Check database connection
            const sqlite3 = require('sqlite3').verbose();
            const dbPath = path.join(__dirname, 'liberty_church.db');
            
            const db = new sqlite3.Database(dbPath, (err) => {
                if (err) {
                    console.error('❌ Database health check failed:', err.message);
                } else {
                    console.log('✅ Database health check passed');
                }
                db.close();
            });

            // Log server uptime
            const uptime = process.uptime();
            const hours = Math.floor(uptime / 3600);
            const minutes = Math.floor((uptime % 3600) / 60);
            console.log(`📊 Server uptime: ${hours}h ${minutes}m`);

        } catch (error) {
            console.error('❌ Health check failed:', error);
        }
    }

    // API methods for manual triggering
    triggerTask(taskName) {
        if (!this.tasks.has(taskName)) {
            throw new Error(`Task ${taskName} not found`);
        }
        
        console.log(`🚀 Manually triggering task: ${taskName}`);
        
        switch (taskName) {
            case 'sermon-refresh':
                this.refreshSermons();
                break;
            case 'database-backup':
                this.backupDatabase();
                break;
            case 'cleanup-uploads':
                this.cleanupOldUploads();
                break;
            case 'health-check':
                this.performHealthCheck();
                break;
            default:
                throw new Error(`Unknown task: ${taskName}`);
        }
    }

    getTaskStatus() {
        const status = {};
        this.tasks.forEach((task, name) => {
            status[name] = {
                running: task.running,
                destroyed: task.destroyed
            };
        });
        return status;
    }

    stopAllTasks() {
        this.tasks.forEach((task, name) => {
            task.destroy();
            console.log(`⏹️ Stopped task: ${name}`);
        });
        this.tasks.clear();
    }
}

module.exports = TaskScheduler;