const Property = require("../models/Property");
const Unit = require("../models/Unit");
const Tenant = require("../models/Tenant");
const Lease = require("../models/Lease");
const Payment = require("../models/Payment");
const Maintenance = require("../models/Maintenance");
const Expense = require("../models/Expense");

const getDashboard = async (req, res) => {
  try {
    const [
      totalProperties,
      totalUnits,
      occupiedUnits,
      vacantUnits,
      maintenanceUnits,
      totalTenants,
      activeLeases,
      paidPayments,
      pendingPayments,
      maintenanceRequests,
      totalExpenses,
      recentPayments,
      recentMaintenance,
      recentLeases,
    ] = await Promise.all([
      Property.countDocuments(),

      Unit.countDocuments(),

      Unit.countDocuments({
        status: "Occupied",
      }),

      Unit.countDocuments({
        status: "Vacant",
      }),

      Unit.countDocuments({
        status: "Maintenance",
      }),

      Tenant.countDocuments({
        status: "Active",
      }),

      Lease.countDocuments({
        status: "Active",
      }),

      Payment.find({
        status: "Paid",
      }).select("amount"),

      Payment.find({
        status: "Pending",
      }).select("amount"),

      Maintenance.countDocuments({
        status: {
          $nin: ["Completed", "Cancelled"],
        },
      }),

      Expense.find({
        status: {
          $nin: ["Cancelled"],
        },
      }).select("amount"),

      Payment.find()
        .populate(
          "tenantId",
          "tenantId name"
        )
        .sort({
          paymentDate: -1,
        })
        .limit(5),

      Maintenance.find()
        .populate(
          "tenantId",
          "tenantId name"
        )
        .populate(
          "propertyId",
          "propertyId name"
        )
        .populate(
          "unitId",
          "unitId unitNumber"
        )
        .sort({
          createdAt: -1,
        })
        .limit(5),

      Lease.find()
        .populate(
          "tenantId",
          "tenantId name"
        )
        .populate(
          "propertyId",
          "propertyId name"
        )
        .populate(
          "unitId",
          "unitId unitNumber"
        )
        .sort({
          createdAt: -1,
        })
        .limit(5),
    ]);

    const totalPaidIncome = paidPayments.reduce(
      (total, payment) => total + payment.amount,
      0
    );

    const totalPendingAmount = pendingPayments.reduce(
      (total, payment) => total + payment.amount,
      0
    );

    const totalExpenseAmount = totalExpenses.reduce(
      (total, expense) => total + expense.amount,
      0
    );

    const netIncome =
      totalPaidIncome - totalExpenseAmount;

    const occupancyRate =
      totalUnits > 0
        ? Number(
            (
              (occupiedUnits / totalUnits) *
              100
            ).toFixed(2)
          )
        : 0;

    res.json({
      success: true,
      data: {
        overview: {
          totalProperties,
          totalUnits,
          occupiedUnits,
          vacantUnits,
          maintenanceUnits,
          totalTenants,
          activeLeases,
          maintenanceRequests,
        },

        financial: {
          totalPaidIncome,
          totalPendingAmount,
          totalExpenseAmount,
          netIncome,
        },

        occupancy: {
          occupied: occupiedUnits,
          vacant: vacantUnits,
          maintenance: maintenanceUnits,
          occupancyRate,
        },

        recent: {
          payments: recentPayments,
          maintenance: recentMaintenance,
          leases: recentLeases,
        },
      },
    });
  } catch (error) {
    console.error("Dashboard error:", error);

    res.status(500).json({
      success: false,
      message: "Failed to retrieve dashboard data",
    });
  }
};

module.exports = {
  getDashboard,
};
