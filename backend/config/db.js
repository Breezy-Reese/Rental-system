const mongoose = require("mongoose");

/**
 * ============================================================
 * PropertyPro - MongoDB Connection
 * ============================================================
 */

const connectDB = async () => {
  const mongoUri = process.env.MONGODB_URI;

  if (!mongoUri) {
    throw new Error(
      "MONGODB_URI environment variable is not configured."
    );
  }

  try {
    const connection = await mongoose.connect(mongoUri, {
      serverSelectionTimeoutMS: 10000,
      connectTimeoutMS: 10000,
      socketTimeoutMS: 45000,
      maxPoolSize: 10,
      minPoolSize: 0,
      retryWrites: true,
    });

    console.log(
      `MongoDB connected: ${connection.connection.host}`
    );

    console.log(
      `Database: ${connection.connection.name}`
    );

    return connection;

  } catch (error) {

    console.error(
      "MongoDB connection failed:",
      error.message
    );

    throw error;
  }
};

/*
|--------------------------------------------------------------------------
| MongoDB Events
|--------------------------------------------------------------------------
*/

mongoose.connection.on("connected", () => {
  console.log("MongoDB connection established.");
});

mongoose.connection.on("disconnected", () => {
  console.warn("MongoDB disconnected.");
});

mongoose.connection.on("reconnected", () => {
  console.log("MongoDB reconnected.");
});

mongoose.connection.on("error", (error) => {
  console.error(
    "MongoDB connection error:",
    error.message
  );
});

module.exports = connectDB;