/**
 * FLIX Database Migrations - Schema Setup & Optimization
 * Run: node api/v1/database/migrations.js
 */

const { Pool } = require('pg');
const config = require('../../../config/environment');

const pool = new Pool({
  connectionString: config.DATABASE.connectionString || 
    `postgresql://${config.DATABASE.user}:${config.DATABASE.password}@${config.DATABASE.host}:${config.DATABASE.port}/${config.DATABASE.database}`,
  ssl: config.DATABASE.ssl,
});

/**
 * All migration SQL statements
 */
const migrations = [
  // Create Tables (already exist, but adding indexes)
  {
    name: 'Create Indexes for Users',
    sql: `
      CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
      CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone);
      CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
    `,
  },
  {
    name: 'Create Indexes for Workers',
    sql: `
      CREATE INDEX IF NOT EXISTS idx_workers_approved ON workers(approved);
      CREATE INDEX IF NOT EXISTS idx_workers_status ON workers(status);
      CREATE INDEX IF NOT EXISTS idx_workers_city ON workers(city);
      CREATE INDEX IF NOT EXISTS idx_workers_email ON workers(email);
      CREATE INDEX IF NOT EXISTS idx_workers_specialization ON workers(specialization);
    `,
  },
  {
    name: 'Create Indexes for Service Requests (Orders)',
    sql: `
      CREATE INDEX IF NOT EXISTS idx_orders_us_id ON service_requests(us_id);
      CREATE INDEX IF NOT EXISTS idx_orders_worker_id ON service_requests(worker_id);
      CREATE INDEX IF NOT EXISTS idx_orders_status ON service_requests(status);
      CREATE INDEX IF NOT EXISTS idx_orders_created_at ON service_requests(created_at DESC);
      CREATE INDEX IF NOT EXISTS idx_orders_specialization ON service_requests(specialization);
      CREATE INDEX IF NOT EXISTS idx_orders_payment_status ON service_requests(payment_status);
      CREATE INDEX IF NOT EXISTS idx_orders_city ON service_requests(city);
    `,
  },
  {
    name: 'Create Indexes for Chat Messages',
    sql: `
      CREATE INDEX IF NOT EXISTS idx_chat_request_id ON chat_messages(request_id);
      CREATE INDEX IF NOT EXISTS idx_chat_sender_id ON chat_messages(sender_id);
      CREATE INDEX IF NOT EXISTS idx_chat_created_at ON chat_messages(created_at DESC);
    `,
  },
  {
    name: 'Create Indexes for Reviews',
    sql: `
      CREATE INDEX IF NOT EXISTS idx_reviews_worker_user ON reviews_worker(user_id, worker_id);
      CREATE INDEX IF NOT EXISTS idx_reviews_user_worker ON reviews_user(worker_id, user_id);
      CREATE INDEX IF NOT EXISTS idx_reviews_worker_rating ON reviews_worker(rating);
    `,
  },
  {
    name: 'Create Indexes for Tasks',
    sql: `
      CREATE INDEX IF NOT EXISTS idx_tasks_worker_id ON tasks(worker_id);
      CREATE INDEX IF NOT EXISTS idx_tasks_request_id ON tasks(request_id);
      CREATE INDEX IF NOT EXISTS idx_tasks_us_id ON tasks(us_id);
      CREATE INDEX IF NOT EXISTS idx_tasks_created_at ON tasks(created_at DESC);
    `,
  },
  {
    name: 'Create Admin Indexes',
    sql: `
      CREATE INDEX IF NOT EXISTS idx_admin_email ON admin(email);
    `,
  },
  {
    name: 'Create Performance View - Worker Stats',
    sql: `
      CREATE OR REPLACE VIEW v_worker_stats AS
      SELECT
        w.id,
        w.name,
        COUNT(DISTINCT sr.id) as total_orders,
        COUNT(DISTINCT CASE WHEN sr.status = 'completed' THEN sr.id END) as completed_orders,
        ROUND(AVG(CASE WHEN rw.rating IS NOT NULL THEN rw.rating ELSE 0 END)::numeric, 2) as average_rating,
        COUNT(DISTINCT rw.id) as total_reviews,
        COALESCE(SUM(sr.worker_price), 0) as total_earnings,
        w.created_at,
        w.updated_at
      FROM workers w
      LEFT JOIN service_requests sr ON w.id = sr.worker_id
      LEFT JOIN reviews_worker rw ON w.id = rw.worker_id
      GROUP BY w.id, w.name, w.created_at, w.updated_at;
    `,
  },
  {
    name: 'Create Performance View - User Stats',
    sql: `
      CREATE OR REPLACE VIEW v_user_stats AS
      SELECT
        u.id,
        u.name,
        COUNT(DISTINCT sr.id) as total_orders,
        COUNT(DISTINCT CASE WHEN sr.status = 'completed' THEN sr.id END) as completed_orders,
        ROUND(AVG(CASE WHEN ru.rating IS NOT NULL THEN ru.rating ELSE 0 END)::numeric, 2) as average_rating,
        COUNT(DISTINCT ru.id) as total_reviews,
        COALESCE(SUM(sr.budget), 0) as total_spent,
        u.created_at,
        u.updated_at
      FROM users u
      LEFT JOIN service_requests sr ON u.id = sr.us_id
      LEFT JOIN reviews_user ru ON u.id = ru.user_id
      GROUP BY u.id, u.name, u.created_at, u.updated_at;
    `,
  },
  {
    name: 'Add Foreign Key Constraints',
    sql: `
      ALTER TABLE IF EXISTS service_requests
      ADD CONSTRAINT IF NOT EXISTS fk_orders_user
      FOREIGN KEY (us_id) REFERENCES users(id) ON DELETE CASCADE;

      ALTER TABLE IF EXISTS service_requests
      ADD CONSTRAINT IF NOT EXISTS fk_orders_worker
      FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE SET NULL;

      ALTER TABLE IF EXISTS chat_messages
      ADD CONSTRAINT IF NOT EXISTS fk_chat_request
      FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE;

      ALTER TABLE IF EXISTS tasks
      ADD CONSTRAINT IF NOT EXISTS fk_tasks_worker
      FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE;

      ALTER TABLE IF EXISTS tasks
      ADD CONSTRAINT IF NOT EXISTS fk_tasks_request
      FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE;

      ALTER TABLE IF EXISTS reviews_worker
      ADD CONSTRAINT IF NOT EXISTS fk_review_worker_user
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

      ALTER TABLE IF EXISTS reviews_worker
      ADD CONSTRAINT IF NOT EXISTS fk_review_worker_worker
      FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE;

      ALTER TABLE IF EXISTS reviews_user
      ADD CONSTRAINT IF NOT EXISTS fk_review_user_worker
      FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE;

      ALTER TABLE IF EXISTS reviews_user
      ADD CONSTRAINT IF NOT EXISTS fk_review_user_user
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
    `,
  },
  {
    name: 'Create Cache Tables for Performance',
    sql: `
      CREATE TABLE IF NOT EXISTS cache (
        key VARCHAR(255) PRIMARY KEY,
        value TEXT,
        expires_at TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
      );

      CREATE INDEX IF NOT EXISTS idx_cache_expires ON cache(expires_at);
    `,
  },
];

/**
 * Run migrations
 */
async function runMigrations() {
  const client = await pool.connect();

  try {
    console.log('🔄 Running migrations...\n');

    for (const migration of migrations) {
      try {
        console.log(`⏳ ${migration.name}...`);
        await client.query(migration.sql);
        console.log(`✅ ${migration.name}\n`);
      } catch (error) {
        if (error.message.includes('already exists')) {
          console.log(`⚠️  ${migration.name} (already exists)\n`);
        } else {
          console.error(`❌ ${migration.name}: ${error.message}\n`);
        }
      }
    }

    console.log('✨ All migrations completed!');
    process.exit(0);
  } catch (error) {
    console.error('❌ Migration failed:', error);
    process.exit(1);
  } finally {
    client.release();
    await pool.end();
  }
}

// Run if executed directly
if (require.main === module) {
  runMigrations();
}

module.exports = { runMigrations, migrations };
