/**
 * ============================================================
 * PropertyPro - System Routes
 * ============================================================
 *
 * Provides runtime / database introspection endpoints.
 * All routes are admin-only.
 */

const express = require("express");
const mongoose = require("mongoose");

const protect   = require("../middleware/auth");
const adminOnly = require("../middleware/admin");

const router = express.Router();

/*
|--------------------------------------------------------------------------
| Helper: current MongoDB ready-state string
|--------------------------------------------------------------------------
*/

const READY_STATES = {
  0: "disconnected",
  1: "connected",
  2: "connecting",
  3: "disconnecting",
};

function databaseState() {
  return (
    READY_STATES[mongoose.connection.readyState] ||
    "unknown"
  );
}

/*
|--------------------------------------------------------------------------
| GET /api/system/db-stats
|--------------------------------------------------------------------------
| Returns:
|  - db name
|  - ping latency (ms)
|  - readyState
|  - collections: { name: count }
|  - serverVersion (if reachable)
|
| Admin only.
|--------------------------------------------------------------------------
*/

router.get(
  "/db-stats",
  protect,
  adminOnly,
  async (req, res) => {
    try {
      const connection = mongoose.connection;

      if (connection.readyState !== 1 || !connection.db) {
        return res.status(503).json({
          success: false,
          message: "Database is not connected.",
          data: {
            state: databaseState(),
          },
        });
      }

      const db = connection.db;

      /*
       * Ping
       */

      let pingMs = null;

      try {
        const start = Date.now();
        await db.admin().ping();
        pingMs = Date.now() - start;
      } catch (pingError) {
        console.warn(
          "db-stats ping failed:",
          pingError.message
        );
      }

      /*
       * Server version (best effort)
       */

      let serverVersion = null;

      try {
        const info = await db.admin().serverInfo();
        serverVersion = info.version || null;
      } catch (_) {
        /* ignore */
      }

      /*
       * Collections + estimated doc counts
       */

      const collections = await db
        .listCollections()
        .toArray();

      const counts = {};

      for (const col of collections) {
        try {
          counts[col.name] = await db
            .collection(col.name)
            .estimatedDocumentCount();
        } catch (countError) {
          counts[col.name] = null;
        }
      }

      return res.status(200).json({
        success: true,
        data: {
          db: db.databaseName,
          state: databaseState(),
          readyState: connection.readyState,
          pingMs,
          serverVersion,
          collections: counts,
          checkedAt: new Date().toISOString(),
        },
      });
    } catch (error) {
      console.error(
        "db-stats error:",
        error.message
      );

      return res.status(500).json({
        success: false,
        message: "Failed to read database stats.",
        error: error.message,
      });
    }
  }
);

module.exports = router;