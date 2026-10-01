const Property = require("../models/Property");
const Unit = require("../models/Unit");
const Tenant = require("../models/Tenant");
const Lease = require("../models/Lease");
const Payment = require("../models/Payment");
const Maintenance = require("../models/Maintenance");
const Expense = require("../models/Expense");
const User = require("../models/User");

// ============================================================
// ADMIN DASHBOARD
// ============================================================

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
        .populate("tenantId", "tenantId name")
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
      (total, payment) =>
        total + Number(payment.amount || 0),
      0
    );

    const totalPendingAmount = pendingPayments.reduce(
      (total, payment) =>
        total + Number(payment.amount || 0),
      0
    );

    const totalExpenseAmount = totalExpenses.reduce(
      (total, expense) =>
        total + Number(expense.amount || 0),
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

// ============================================================
// ADMIN CUSTOMER LIST
// ============================================================

const getCustomers = async (req, res) => {
  try {
    const customers = await User.find({
      role: "Customer",
    })
      .select("name email phone status createdAt")
      .sort({
        createdAt: -1,
      })
      .lean();

    const customerIds = customers.map(
      (customer) => customer._id
    );

    const tenants = await Tenant.find({
      userId: {
        $in: customerIds,
      },
    })
      .select(
        "_id userId tenantId propertyId unitId status"
      )
      .lean();

    const tenantMap = new Map();

    for (const tenant of tenants) {
      if (tenant.userId) {
        tenantMap.set(
          tenant.userId.toString(),
          tenant
        );
      }
    }

    const tenantIds = tenants.map(
      (tenant) => tenant._id
    );

    const activeLeases = await Lease.find({
      tenantId: {
        $in: tenantIds,
      },
      status: "Active",
    })
      .select(
        "_id tenantId propertyId unitId leaseId startDate endDate rent deposit status"
      )
      .lean();

    const activeLeaseMap = new Map();

    for (const lease of activeLeases) {
      if (lease.tenantId) {
        activeLeaseMap.set(
          lease.tenantId.toString(),
          lease
        );
      }
    }

    const result = customers.map((customer) => {
      const tenant = tenantMap.get(
        customer._id.toString()
      ) || null;

      const activeLease = tenant
        ? activeLeaseMap.get(
            tenant._id.toString()
          ) || null
        : null;

      return {
        id: customer._id,
        name: customer.name,
        email: customer.email,
        phone: customer.phone || "",
        status: customer.status,
        createdAt: customer.createdAt,

        tenantId: tenant?._id || null,
        tenantReference: tenant?.tenantId || null,

        hasTenant: Boolean(tenant),

        hasActiveLease: Boolean(
          activeLease
        ),

        propertyId:
          activeLease?.propertyId ||
          tenant?.propertyId ||
          null,

        unitId:
          activeLease?.unitId ||
          tenant?.unitId ||
          null,

        leaseId:
          activeLease?.leaseId ||
          null,

        leaseStart:
          activeLease?.startDate ||
          null,

        leaseEnd:
          activeLease?.endDate ||
          null,

        rent:
          activeLease?.rent ??
          null,

        deposit:
          activeLease?.deposit ??
          null,
      };
    });

    res.json({
      success: true,
      count: result.length,
      data: result,
    });
  } catch (error) {
    console.error(
      "Get customers error:",
      error
    );

    res.status(500).json({
      success: false,
      message: "Failed to retrieve customers",
    });
  }
};

module.exports = {
  getDashboard,
  getCustomers,
};