/**
 * MariaDB helpers for E2E test verification and lock simulation.
 *
 * Connects to MariaDB via mysql2 (TCP). Default connection values match
 * the devcontainer's docker-compose.yml — no configuration needed when
 * running inside the standard devcontainer.
 */

import mysql from 'mysql2/promise';

const DB_CONFIG = {
  host: process.env.DB_HOST || 'localhost',
  port: parseInt(process.env.DB_PORT || '3306', 10),
  user: process.env.DB_USER || 'prestashop',
  password: process.env.DB_PASSWORD || 'prestashop123',
  database: process.env.DB_NAME || 'prestashop',
};

let pool;

function getPool() {
  if (!pool) {
    pool = mysql.createPool({ ...DB_CONFIG, waitForConnections: true, connectionLimit: 5 });
  }
  return pool;
}

export async function closePool() {
  if (pool) {
    await pool.end();
    pool = null;
  }
}

async function queryScalar(sql, params = []) {
  const [rows] = await getPool().execute(sql, params);
  return rows[0] ? Object.values(rows[0])[0] : null;
}

/**
 * Acquires a MariaDB named lock on a dedicated connection.
 * The lock is held as long as the connection stays open.
 * Call `release()` on the returned object to release the lock and close the connection.
 *
 * @param {string} key
 * @returns {Promise<{ release: () => Promise<void> }>}
 */
export async function holdLock(key) {
  const connection = await mysql.createConnection(DB_CONFIG);
  const [rows] = await connection.execute('SELECT GET_LOCK(?, 0) AS acquired', [key]);
  const acquired = rows[0].acquired === 1;

  if (!acquired) {
    await connection.end();
    throw new Error(`Could not acquire lock for key: ${key}`);
  }

  return {
    release: async () => {
      await connection.execute('SELECT RELEASE_LOCK(?)', [key]);
      await connection.end();
    },
  };
}

/**
 * @param {string} key
 * @returns {Promise<boolean>}
 */
export async function isLockHeld(key) {
  const result = await queryScalar('SELECT IS_USED_LOCK(?)', [key]);
  return result !== null;
}

/**
 * @param {string} token
 * @returns {Promise<number>}
 */
export async function getOrderCountByToken(token) {
  const result = await queryScalar(
    'SELECT COUNT(*) FROM ps_orders WHERE id_cart = (SELECT cart_id FROM ps_webpay_rest_transactions WHERE token = ? LIMIT 1)',
    [token]
  );
  return Number(result);
}

/**
 * @param {string} token
 * @returns {Promise<number>}
 */
export async function getTransactionStatus(token) {
  const result = await queryScalar(
    'SELECT status FROM ps_webpay_rest_transactions WHERE token = ? LIMIT 1',
    [token]
  );
  return Number(result);
}
