const Expense = require("../models/Expense");
const Property = require("../models/Property");
const Notification = require("../models/Notification");

const getExpenses = async (req, res) => {
  try {
    const expenses = await Expense.find()
      .populate("propertyId", "propertyId name location")
      .sort({ expenseDate: -1, createdAt: -1 });

    res.json({
      success: true,
      count: expenses.length,
      data: expenses,
    });
  } catch (error) {
    console.error("Get expenses error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve expenses",
    });
  }
};

const getExpense = async (req, res) => {
  try {
    const expense = await Expense.findById(req.params.id)
      .populate("propertyId", "propertyId name location");

    if (!expense) {
      return res.status(404).json({
        success: false,
        message: "Expense not found",
      });
    }

    res.json({
      success: true,
      data: expense,
    });
  } catch (error) {
    console.error("Get expense error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve expense",
    });
  }
};

const createExpense = async (req, res) => {
  try {
    const {
      expenseId,
      propertyId,
      category,
      description,
      amount,
      expenseDate,
      status,
    } = req.body;

    if (
      !expenseId ||
      !propertyId ||
      !category ||
      amount === undefined
    ) {
      return res.status(400).json({
        success: false,
        message:
          "Expense ID, property, category and amount are required",
      });
    }

    if (Number(amount) <= 0) {
      return res.status(400).json({
        success: false,
        message: "Expense amount must be greater than zero",
      });
    }

    const existingExpense = await Expense.findOne({
      expenseId,
    });

    if (existingExpense) {
      return res.status(409).json({
        success: false,
        message: "Expense ID already exists",
      });
    }

    const property = await Property.findById(propertyId);

    if (!property) {
      return res.status(404).json({
        success: false,
        message: "Property not found",
      });
    }

    const expense = await Expense.create({
      expenseId,
      propertyId,
      category,
      description,
      amount: Number(amount),
      expenseDate: expenseDate || new Date(),
      status: status || "Pending",
    });

    await Notification.create({
      recipientRole: "Administrator",
      recipientId: null,
      type: "expense_created",
      title: "New Expense Recorded",
      message: `A new ${category} expense of KES ${Number(
        amount
      ).toLocaleString()} was recorded for ${property.name}.`,
      data: {
        expenseId: expense._id,
        propertyId: property._id,
        amount: Number(amount),
        category,
        status: status || "Pending",
      },
    });

    const populatedExpense = await Expense.findById(expense._id)
      .populate("propertyId", "propertyId name location");

    res.status(201).json({
      success: true,
      message: "Expense created successfully",
      data: populatedExpense,
    });
  } catch (error) {
    console.error("Create expense error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to create expense",
    });
  }
};

const updateExpense = async (req, res) => {
  try {
    const expense = await Expense.findById(req.params.id);

    if (!expense) {
      return res.status(404).json({
        success: false,
        message: "Expense not found",
      });
    }

    const allowedFields = [
      "category",
      "description",
      "amount",
      "expenseDate",
      "status",
      "propertyId",
    ];

    allowedFields.forEach((field) => {
      if (req.body[field] !== undefined) {
        expense[field] = req.body[field];
      }
    });

    if (Number(expense.amount) <= 0) {
      return res.status(400).json({
        success: false,
        message: "Expense amount must be greater than zero",
      });
    }

    const property = await Property.findById(expense.propertyId);

    if (!property) {
      return res.status(404).json({
        success: false,
        message: "Property not found",
      });
    }

    await expense.save();

    const updatedExpense = await Expense.findById(expense._id)
      .populate("propertyId", "propertyId name location");

    res.json({
      success: true,
      message: "Expense updated successfully",
      data: updatedExpense,
    });
  } catch (error) {
    console.error("Update expense error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to update expense",
    });
  }
};

const deleteExpense = async (req, res) => {
  try {
    const expense = await Expense.findById(req.params.id);

    if (!expense) {
      return res.status(404).json({
        success: false,
        message: "Expense not found",
      });
    }

    await Expense.findByIdAndDelete(req.params.id);

    res.json({
      success: true,
      message: "Expense deleted successfully",
    });
  } catch (error) {
    console.error("Delete expense error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to delete expense",
    });
  }
};

module.exports = {
  getExpenses,
  getExpense,
  createExpense,
  updateExpense,
  deleteExpense,
};
