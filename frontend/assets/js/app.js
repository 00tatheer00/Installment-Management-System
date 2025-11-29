const API_BASE = "http://localhost/installment_api_clean/api";

/* --------------------- API HELPER --------------------- */
async function api(path, options = {}) {
    const res = await fetch(API_BASE + path, {
        credentials: "include",
        headers: { "Content-Type": "application/json" },
        ...options
    });

    if (!res.ok) {
        let msg = "Error " + res.status;
        try {
            const j = await res.json();
            msg = j.message || msg;
        } catch {}
        throw new Error(msg);
    }
    return res.json();
}

/* --------------------- MAIN INIT --------------------- */
document.addEventListener("DOMContentLoaded", () => {
    document.getElementById("year").textContent = new Date().getFullYear();

    setupTheme();
    initAuth();

    window.addEventListener("hashchange", handleRoute);
});

/* --------------------- THEME --------------------- */
function setupTheme() {
    const body = document.body;
    const saved = localStorage.getItem("theme");
    if (saved === "dark") body.classList.add("dark");

    document.getElementById("themeToggle").onclick = () => {
        body.classList.toggle("dark");
        localStorage.setItem("theme", body.classList.contains("dark") ? "dark" : "light");
    };
}

/* --------------------- AUTH INIT --------------------- */
async function initAuth() {
    document.getElementById("loginBtn").onclick = login;
    document.getElementById("logoutBtn").onclick = logout;

    try {
        const res = await api("/auth/me.php", { method: "GET" });
        if (res.status === "success" && res.user) {
            setLoggedIn(res.user);
            location.hash = "#dashboard";
            handleRoute();
            return;
        }
    } catch {}

    setLoggedOut();
}

/* --------------------- LOGIN --------------------- */
async function login() {
    const u = document.getElementById("loginUsername").value.trim();
    const p = document.getElementById("loginPassword").value;
    const errBox = document.getElementById("loginError");
    errBox.style.display = "none";

    try {
        const res = await api("/auth/login.php", {
            method: "POST",
            body: JSON.stringify({ username: u, password: p })
        });

        if (res.status === "success") {
            Swal.fire("Success", "Login successful!", "success");
            setLoggedIn(res.user);
            location.hash = "#dashboard";
            handleRoute();
        } else {
            errBox.textContent = res.message || "Login failed";
            errBox.style.display = "block";
        }

    } catch (e) {
        errBox.textContent = e.message;
        errBox.style.display = "block";
    }
}

/* --------------------- LOGOUT --------------------- */
async function logout() {
    await api("/auth/logout.php", { method: "POST" });

    Swal.fire("Logged Out", "You have been logged out.", "info");

    setLoggedOut();
    location.hash = "#login";
    showView("login");
}

/* --------------------- LOGIN/LOGOUT UI STATE --------------------- */
function setLoggedIn(user) {
    document.getElementById("userInfo").textContent = `${user.name} (${user.role})`;
    document.getElementById("logoutBtn").style.display = "inline-block";
    document.getElementById("sidebar").style.display = "block";
    document.getElementById("view-login").style.display = "none";
}

function setLoggedOut() {
    document.getElementById("userInfo").textContent = "";
    document.getElementById("logoutBtn").style.display = "none";
    document.getElementById("sidebar").style.display = "none";
    showView("login");
}

/* --------------------- ROUTER (Fixed) --------------------- */
function handleRoute() {
    const loggedIn = document.getElementById("sidebar").style.display !== "none";
    let view = location.hash.replace("#", "");

    if (!loggedIn) {
        showView("login");
        return;
    }

    if (!view) {
        view = "dashboard";
        location.hash = "#dashboard";
    }

    highlightSidebar(view);
    showView(view);

    if (view === "dashboard") loadDashboard();
    if (view === "customers") loadCustomers();
    if (view === "products") loadProducts();
    if (view === "plans") loadPlans();
    if (view === "payments") loadPayments();
    if (view === "reports") loadReports();
    if (view === "backup") loadBackup();
}

/* --------------------- SHOW VIEW --------------------- */
function showView(v) {
    const pages = [
        "login", "dashboard", "customers", "products",
        "plans", "payments", "reports", "backup"
    ];

    pages.forEach(p => {
        const el = document.getElementById(`view-${p}`);
        if (el) el.style.display = p === v ? "block" : "none";
    });
}

/* --------------------- SIDEBAR ACTIVE --------------------- */
function highlightSidebar(hash) {
    document.querySelectorAll(".sidebar nav a").forEach(a => {
        a.classList.remove("active");
        if (a.getAttribute("href") === "#" + hash) {
            a.classList.add("active");
        }
    });
}

/* --------------------- SKELETON LOADER --------------------- */
function skeletonCards(count = 6) {
    return Array(count).fill(`
        <div class="card glass" style="height:90px; animation:pulse 1.2s infinite;">
        </div>
    `).join("");
}

function skeletonTable(rows = 6) {
    return `
      <tbody>
        ${Array(rows).fill("<tr><td colspan='6' style='height:22px; animation:pulse 1.3s infinite;'></td></tr>").join("")}
      </tbody>
    `;
}

/* --------------------- DASHBOARD --------------------- */
async function loadDashboard() {
    document.getElementById("dashboardCards").innerHTML = skeletonCards();
    document.getElementById("lastPaymentsTable").innerHTML = skeletonTable();

    try {
        const res = await api("/dashboard.php");
        const d = res.data;

        document.getElementById("dashboardCards").innerHTML = `
          <div class="card glass"><h3>Total Customers</h3><p>${d.total_customers}</p></div>
          <div class="card glass"><h3>Total Products</h3><p>${d.total_products}</p></div>
          <div class="card glass"><h3>Active Plans</h3><p>${d.active_plans}</p></div>
          <div class="card glass"><h3>Completed</h3><p>${d.completed_plans}</p></div>
          <div class="card glass"><h3>Overdue</h3><p>${d.overdue_plans}</p></div>
          <div class="card glass"><h3>Today's Collection</h3><p>Rs. ${d.today_collection.toFixed(2)}</p></div>
        `;

        const ctx = document.getElementById("monthlyCollectionChart");
        const labels = [...Array(12).keys()].map(m => m + 1);
        const values = labels.map(m => d.monthly_collection?.[m] || 0);

        new Chart(ctx, {
            type: "bar",
            data: {
                labels,
                datasets: [{
                    label: "Collection",
                    data: values,
                    backgroundColor: "#3a86ff"
                }]
            }
        });

        const tbl = document.getElementById("lastPaymentsTable");
        tbl.innerHTML = `
            <thead><tr><th>Date</th><th>Customer</th><th>Amount</th><th>Note</th></tr></thead>
            <tbody>
              ${(d.last_payments || []).map(r => `
                <tr>
                  <td>${r.payment_date}</td>
                  <td>${r.customer_name}</td>
                  <td>Rs. ${parseFloat(r.amount).toFixed(2)}</td>
                  <td>${r.note || ""}</td>
                </tr>
              `).join("")}
            </tbody>
        `;
    } catch (e) {
        console.error(e);
    }
}

/* --------------------- CUSTOMERS --------------------- */
async function loadCustomers() {
    if (!document.getElementById("custSaveBtn").dataset.bound) {
        document.getElementById("custSaveBtn").dataset.bound = "1";
        document.getElementById("custSaveBtn").onclick = saveCustomer;

        document.getElementById("custResetBtn").onclick = () => {
            ["custId", "custName", "custPhone", "custCnic", "custAddress"]
                .forEach(id => document.getElementById(id).value = "");
        };

        document.getElementById("custSearch").onkeyup = e => renderCustomers(e.target.value.trim());
    }

    renderCustomers("");
}

async function renderCustomers(q) {
    const res = await api("/customers.php?q=" + encodeURIComponent(q));
    const data = res.data || [];

    document.getElementById("custTable").innerHTML = `
      <thead><tr><th>Name</th><th>Phone</th><th>CNIC</th><th>Address</th><th>Action</th></tr></thead>
      <tbody>
        ${data.map(c => `
          <tr>
            <td>${c.name}</td>
            <td>${c.phone || ""}</td>
            <td>${c.cnic || ""}</td>
            <td>${c.address || ""}</td>
            <td>
              <button class="btn btn-primary btn-sm" onclick='editCustomer(${JSON.stringify(c)})'>
                <i class="fa-solid fa-pencil"></i> Edit
              </button>
              <button class="btn btn-danger btn-sm" onclick='deleteCustomer(${c.id})'>
                <i class="fa-solid fa-trash"></i> Delete
              </button>
            </td>
          </tr>`).join("")}
      </tbody>
    `;
}

function editCustomer(c) {
    ["id","name","phone","cnic","address"].forEach(field => {
        document.getElementById("cust" + field.charAt(0).toUpperCase() + field.slice(1)).value = c[field] || "";
    });
}

async function saveCustomer() {
    const id = document.getElementById("custId").value;
    const data = {
        name: document.getElementById("custName").value.trim(),
        phone: document.getElementById("custPhone").value.trim(),
        cnic: document.getElementById("custCnic").value.trim(),
        address: document.getElementById("custAddress").value.trim()
    };

    if (!data.name) return Swal.fire("Error", "Customer name required", "error");

    await api("/customers.php", {
        method: id ? "PUT" : "POST",
        body: JSON.stringify({ id, ...data })
    });

    Swal.fire("Saved", "Customer updated!", "success");
    ["custId", "custName", "custPhone", "custCnic", "custAddress"]
        .forEach(id => document.getElementById(id).value = "");

    renderCustomers(document.getElementById("custSearch").value.trim());
}

async function deleteCustomer(id) {
    const result = await Swal.fire({
        title: "Delete Customer?",
        text: "This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Yes, delete it!"
    });

    if (result.isConfirmed) {
        try {
            await api("/customers.php", {
                method: "DELETE",
                body: JSON.stringify({ id })
            });
            Swal.fire("Deleted!", "Customer has been deleted.", "success");
            renderCustomers(document.getElementById("custSearch").value.trim());
        } catch (e) {
            Swal.fire("Error", e.message, "error");
        }
    }
}

/* --------------------- PRODUCTS --------------------- */
async function loadProducts() {
    if (!document.getElementById("prodSaveBtn").dataset.bound) {
        document.getElementById("prodSaveBtn").dataset.bound = "1";
        document.getElementById("prodSaveBtn").onclick = saveProduct;

        document.getElementById("prodResetBtn").onclick = () => {
            ["prodId", "prodName", "prodPrice", "prodDesc"]
                .forEach(id => document.getElementById(id).value = "");
        };

        document.getElementById("prodSearch").onkeyup = e => renderProducts(e.target.value.trim());
    }

    renderProducts("");
}

async function renderProducts(q) {
    const res = await api("/products.php?q=" + encodeURIComponent(q));
    const data = res.data || [];

    document.getElementById("prodTable").innerHTML = `
      <thead><tr><th>Name</th><th>Price</th><th>Description</th><th>Action</th></tr></thead>
      <tbody>
        ${data.map(p => `
          <tr>
            <td>${p.name}</td>
            <td>Rs. ${parseFloat(p.price).toFixed(2)}</td>
            <td>${p.description || ""}</td>
            <td>
              <button class="btn btn-primary btn-sm" onclick='editProduct(${JSON.stringify(p)})'>
                <i class="fa-solid fa-pencil"></i> Edit
              </button>
              <button class="btn btn-danger btn-sm" onclick='deleteProduct(${p.id})'>
                <i class="fa-solid fa-trash"></i> Delete
              </button>
            </td>
          </tr>`).join("")}
      </tbody>
    `;
}

function editProduct(p) {
    document.getElementById("prodId").value = p.id || "";
    document.getElementById("prodName").value = p.name || "";
    document.getElementById("prodPrice").value = p.price || "";
    document.getElementById("prodDesc").value = p.description || "";
}

async function deleteProduct(id) {
    const result = await Swal.fire({
        title: "Delete Product?",
        text: "This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Yes, delete it!"
    });

    if (result.isConfirmed) {
        try {
            await api("/products.php", {
                method: "DELETE",
                body: JSON.stringify({ id })
            });
            Swal.fire("Deleted!", "Product has been deleted.", "success");
            renderProducts(document.getElementById("prodSearch").value.trim());
        } catch (e) {
            Swal.fire("Error", e.message, "error");
        }
    }
}

async function saveProduct() {
    const id = document.getElementById("prodId").value;
    const data = {
        name: document.getElementById("prodName").value.trim(),
        price: parseFloat(document.getElementById("prodPrice").value) || 0,
        description: document.getElementById("prodDesc").value
    };

    if (!data.name) return Swal.fire("Error", "Product name required", "error");

    await api("/products.php", {
        method: id ? "PUT" : "POST",
        body: JSON.stringify({ id, ...data })
    });

    Swal.fire("Saved", "Product updated!", "success");
    ["prodId", "prodName", "prodPrice", "prodDesc"]
        .forEach(id => document.getElementById(id).value = "");

    renderProducts(document.getElementById("prodSearch").value.trim());
}

/* --------------------- PLANS --------------------- */
async function loadPlans() {
    if (!document.getElementById("planSaveBtn").dataset.bound) {
        document.getElementById("planSaveBtn").dataset.bound = "1";
        document.getElementById("planSaveBtn").onclick = savePlan;

        document.getElementById("planResetBtn").onclick = () => {
            ["planId", "planCustomer", "planProduct", "planTotal", "planDown", "planSchedule", "planInstallAmt"]
                .forEach(id => document.getElementById(id).value = "");
        };

        document.getElementById("planSearch").onkeyup = e => renderPlans(e.target.value.trim());
    }

    await fillPlanDropdowns();
    renderPlans("");
}

async function fillPlanDropdowns() {
    const cust = await api("/customers.php?q=");
    const prod = await api("/products.php?q=");

    document.getElementById("planCustomer").innerHTML =
        cust.data.map(c => `<option value="${c.id}">${c.name}</option>`).join("");

    document.getElementById("planProduct").innerHTML =
        prod.data.map(p => `<option value="${p.id}">${p.name}</option>`).join("");
}

async function renderPlans(q) {
    const res = await api("/plans.php?q=" + encodeURIComponent(q));
    const d = res.data || [];

    document.getElementById("planTable").innerHTML = `
      <thead>
        <tr>
          <th>Customer</th><th>Product</th>
          <th>Total</th><th>Down</th>
          <th>Remaining</th><th>Status</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        ${d.map(p => `
          <tr>
            <td>${p.customer}</td>
            <td>${p.product}</td>
            <td>Rs. ${parseFloat(p.total_amount).toFixed(2)}</td>
            <td>Rs. ${parseFloat(p.down_payment).toFixed(2)}</td>
            <td>Rs. ${parseFloat(p.remaining_amount).toFixed(2)}</td>
            <td><span class="badge ${p.status === 'Completed' ? 'badge-success' : p.status === 'Overdue' ? 'badge-danger' : 'badge-warning'}">${p.status}</span></td>
            <td>
              <button class="btn btn-primary btn-sm" onclick='editPlan(${JSON.stringify(p)})'>
                <i class="fa-solid fa-pencil"></i> Edit
              </button>
              <button class="btn btn-danger btn-sm" onclick='deletePlan(${p.id})'>
                <i class="fa-solid fa-trash"></i> Delete
              </button>
            </td>
          </tr>`).join("")}
      </tbody>
    `;
}

function editPlan(p) {
    document.getElementById("planId").value = p.id || "";
    document.getElementById("planCustomer").value = p.customer_id || "";
    document.getElementById("planProduct").value = p.product_id || "";
    document.getElementById("planTotal").value = p.total_amount || "";
    document.getElementById("planDown").value = p.down_payment || "";
    document.getElementById("planSchedule").value = p.schedule_type || "monthly";
    document.getElementById("planInstallAmt").value = p.installment_amount || "";
}

async function deletePlan(id) {
    const result = await Swal.fire({
        title: "Delete Plan?",
        text: "This will also delete all associated payments. This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Yes, delete it!"
    });

    if (result.isConfirmed) {
        try {
            await api("/plans.php", {
                method: "DELETE",
                body: JSON.stringify({ id })
            });
            Swal.fire("Deleted!", "Plan has been deleted.", "success");
            renderPlans(document.getElementById("planSearch").value.trim());
        } catch (e) {
            Swal.fire("Error", e.message, "error");
        }
    }
}

async function savePlan() {
    const id = document.getElementById("planId").value;
    const data = {
        customer_id: document.getElementById("planCustomer").value,
        product_id: document.getElementById("planProduct").value,
        total_amount: parseFloat(document.getElementById("planTotal").value) || 0,
        down_payment: parseFloat(document.getElementById("planDown").value) || 0,
        schedule_type: document.getElementById("planSchedule").value,
        installment_amount: parseFloat(document.getElementById("planInstallAmt").value) || 0
    };

    if (!data.customer_id || !data.product_id || data.total_amount <= 0) {
        return Swal.fire("Error", "Please fill all required fields", "error");
    }

    await api("/plans.php", {
        method: id ? "PUT" : "POST",
        body: JSON.stringify({ id, ...data })
    });

    Swal.fire("Saved", id ? "Plan updated!" : "Plan added!", "success");
    ["planId", "planCustomer", "planProduct", "planTotal", "planDown", "planSchedule", "planInstallAmt"]
        .forEach(id => document.getElementById(id).value = "");

    await fillPlanDropdowns();
    renderPlans(document.getElementById("planSearch").value.trim());
}

/* --------------------- PAYMENTS --------------------- */
async function loadPayments() {
    if (!document.getElementById("payBtn").dataset.bound) {
        document.getElementById("payBtn").dataset.bound = "1";
        document.getElementById("payBtn").onclick = savePayment;
    }

    await fillPayDropdown();
    renderPayments();
}

async function fillPayDropdown() {
    const res = await api("/plans.php?q=");
    document.getElementById("payPlan").innerHTML =
        res.data.map(p => `<option value="${p.id}">${p.customer} - ${p.product}</option>`).join("");
}

async function renderPayments() {
    const res = await api("/payments.php");
    const data = res.data || [];

    document.getElementById("payTable").innerHTML = `
      <thead><tr><th>Date</th><th>Customer</th><th>Amount</th><th>Note</th><th>Action</th></tr></thead>
      <tbody>
        ${data.map(p => `
          <tr>
            <td>${p.payment_date}</td>
            <td>${p.customer_name}</td>
            <td>Rs. ${parseFloat(p.amount).toFixed(2)}</td>
            <td>${p.note || ""}</td>
            <td>
              <button class="btn btn-danger btn-sm" onclick='deletePayment(${p.id})'>
                <i class="fa-solid fa-trash"></i> Delete
              </button>
            </td>
          </tr>
        `).join("")}
      </tbody>
    `;
}

async function deletePayment(id) {
    const result = await Swal.fire({
        title: "Delete Payment?",
        text: "This will restore the amount to the plan. This action cannot be undone!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#ef4444",
        cancelButtonColor: "#6b7280",
        confirmButtonText: "Yes, delete it!"
    });

    if (result.isConfirmed) {
        try {
            await api("/payments.php", {
                method: "DELETE",
                body: JSON.stringify({ id })
            });
            Swal.fire("Deleted!", "Payment has been deleted.", "success");
            renderPayments();
        } catch (e) {
            Swal.fire("Error", e.message, "error");
        }
    }
}

async function savePayment() {
    const data = {
        plan_id: document.getElementById("payPlan").value,
        amount: parseFloat(document.getElementById("payAmount").value) || 0,
        note: document.getElementById("payNote").value
    };

    if (!data.plan_id || data.amount <= 0) {
        return Swal.fire("Error", "Please select a plan and enter amount", "error");
    }

    await api("/payments.php", { method: "POST", body: JSON.stringify(data) });

    Swal.fire("Saved", "Payment Added!", "success");
    document.getElementById("payAmount").value = "";
    document.getElementById("payNote").value = "";

    await fillPayDropdown();
    renderPayments();
}

/* --------------------- REPORTS --------------------- */
async function loadReports() {
    document.getElementById("dailyReportBtn").onclick = loadDailyReport;
    document.getElementById("monthlyReportBtn").onclick = loadMonthlyReport;
    document.getElementById("custReportBtn").onclick = loadCustomerReport;

    const res = await api("/customers.php?q=");
    document.getElementById("reportCust").innerHTML =
        res.data.map(c => `<option value="${c.id}">${c.name}</option>`).join("");
}

async function loadDailyReport() {
    const res = await api("/reports/daily.php");

    document.getElementById("dailyReportTable").innerHTML = `
      <thead><tr><th>Date</th><th>Customer</th><th>Amount</th><th>Note</th></tr></thead>
      <tbody>
        ${res.data.map(r => `
          <tr>
            <td>${r.payment_date}</td>
            <td>${r.customer}</td>
            <td>${r.amount}</td>
            <td>${r.note || ""}</td>
          </tr>`).join("")}
      </tbody>
    `;
}

async function loadMonthlyReport() {
    const res = await api("/reports/monthly.php");

    document.getElementById("monthlyReportTable").innerHTML = `
      <thead><tr><th>Month</th><th>Total</th></tr></thead>
      <tbody>
        ${Object.keys(res.data).map(m => `
          <tr><td>${m}</td><td>${res.data[m]}</td></tr>
        `).join("")}
      </tbody>
    `;
}

async function loadCustomerReport() {
    const cid = document.getElementById("reportCust").value;
    const res = await api("/reports/customer.php?cid=" + cid);

    document.getElementById("custReportTable").innerHTML = `
      <thead><tr><th>Date</th><th>Plan</th><th>Amount</th><th>Note</th></tr></thead>
      <tbody>
        ${res.data.map(r => `
          <tr>
            <td>${r.payment_date}</td>
            <td>${r.plan_id}</td>
            <td>${r.amount}</td>
            <td>${r.note || ""}</td>
          </tr>
        `).join("")}
      </tbody>
    `;
}

/* --------------------- BACKUP --------------------- */
function loadBackup() {
    document.getElementById("backupExportBtn").onclick = () => {
        window.location.href = API_BASE + "/backup/export.php";
    };

    document.getElementById("backupRestoreBtn").onclick = async () => {
        const file = document.getElementById("backupFile").files[0];
        const msg = document.getElementById("backupMsg");

        if (!file) {
            msg.textContent = "Please select a SQL file.";
            return;
        }

        const form = new FormData();
        form.append("file", file);

        try {
            const res = await fetch(API_BASE + "/backup/restore.php", {
                method: "POST",
                credentials: "include",
                body: form
            });

            const json = await res.json();
            msg.textContent = json.message;
        } catch {
            msg.textContent = "Error restoring backup.";
        }
    };
}
