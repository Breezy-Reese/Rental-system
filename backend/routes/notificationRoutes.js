const express = require("express");

const protect = require("../middleware/auth");

const {
  getNotifications,
  getNotification,
  markAsRead,
  markAllAsRead,
  deleteNotification,
  replyToNotification,
} = require("../controllers/notificationController");

const router = express.Router();

router.use(protect);

/*
|--------------------------------------------------------------------------
| List notifications
|--------------------------------------------------------------------------
*/

router.get("/", getNotifications);


/*
|--------------------------------------------------------------------------
| Mark all as read
|--------------------------------------------------------------------------
| Must come BEFORE /:id
*/

router.patch("/read-all", markAllAsRead);


/*
|--------------------------------------------------------------------------
| Individual notification
|--------------------------------------------------------------------------
*/

router.get("/:id", getNotification);

router.patch("/:id/read", markAsRead);


/*
|--------------------------------------------------------------------------
| Admin feedback
|--------------------------------------------------------------------------
*/

router.post(
  "/:id/reply",
  replyToNotification
);


/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

router.delete("/:id", deleteNotification);

module.exports = router;