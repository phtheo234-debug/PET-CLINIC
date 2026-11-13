let inventoryItems = [];
let isAdminInventory = false;
let isAdminSpa = false;

document.addEventListener("DOMContentLoaded", async () => {
    await initPage();
    const user = getCurrentUser();
    isAdminInventory = !!user && user.vaitro === "QUAN_TRI";
    isAdminSpa = isAdminInventory;

    if (isAdminInventory) {
        document.getElementById("btnAddSpaService")?.classList.remove("d-none");
        document.getElementById("spaActionHeader")?.classList.remove("d-none");
        document.getElementById("btnAddInventory")?.classList.remove("d-none");
        document.getElementById("inventoryActionHeader")?.classList.remove("d-none");
    }

    document.getElementById("inventoryForm")?.addEventListener("submit", saveInventoryItem);
    document.getElementById("spaServiceForm")?.addEventListener("submit", saveSpaService);
    document.getElementById("inventory_category")?.addEventListener("change", toggleMedicineDosageFields);
    toggleMedicineDosageFields();
    await loadInventory();
});

async function loadInventory() {
    try {
        const res = await apiRequest("/inventory");
        inventoryItems = Array.isArray(res) ? res : (Array.isArray(res.data) ? res.data : []);
        renderInventory(inventoryItems);
    } catch (err) {
        document.getElementById("inventoryTableBody").innerHTML =
            `<tr><td colspan="${isAdminInventory ? 9 : 8}" class="text-danger text-center">${escapeInventoryHtml(err.message || "Lỗi tải kho")}</td></tr>`;
    }
}

function escapeInventoryHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (char) => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
    })[char]);
}

function renderInventory(items) {
    const today = new Date().toISOString().split("T")[0];
    const categoryLabels = {
        THUOC: "Thuốc",
        VAC_XIN: "Vắc-xin",
        THUC_AN: "Thức ăn",
        VAT_TU: "Vật tư",
    };
    const goods = items.filter((item) => item.danhmuc !== "DICH_VU_SPA");

    document.getElementById("inventoryTableBody").innerHTML = goods.length
        ? goods.map((item) => {
            const expiry = item.ngayhethan || item.batches?.[0]?.ngayhethan || "-";
            const batchNo = item.solo || item.batches?.[0]?.solo || "-";
            const qty = item.tong_ton_kho ?? item.soluong ?? item.tong_ton ?? 0;
            const isExpired = expiry !== "-" && expiry < today;
            const statusHtml = isExpired
                ? '<span class="badge badge-danger">Quá hạn - Khóa xuất (BR-06)</span>'
                : '<span class="badge badge-success">Khả dụng (FEFO)</span>';
            const actions = isAdminInventory ? `
                <td class="text-nowrap">
                    <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="editInventoryItem(${Number(item.item_id)})">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteInventoryItem(${Number(item.item_id)})">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </td>` : "";

            return `
                <tr class="${isExpired ? "table-danger" : ""}">
                    <td><code>${escapeInventoryHtml(item.masanpham)}</code></td>
                    <td><strong>${escapeInventoryHtml(item.ten_sanpham || item.name)}</strong></td>
                    <td>${categoryLabels[item.danhmuc] || escapeInventoryHtml(item.danhmuc)}</td>
                    <td>${escapeInventoryHtml(batchNo)}</td>
                    <td><strong>${Number(qty)}</strong></td>
                    <td>${escapeInventoryHtml(expiry)}</td>
                    <td>${formatCurrency(item.dongia)}</td>
                    <td>${statusHtml}</td>
                    ${actions}
                </tr>`;
        }).join("")
        : `<tr><td colspan="${isAdminInventory ? 9 : 8}" class="text-center text-muted">Chưa có thuốc, vắc-xin, thức ăn hoặc vật tư trong kho</td></tr>`;

    const services = items.filter((item) => item.danhmuc === "DICH_VU_SPA");
    document.getElementById("spaTableBody").innerHTML = services.length
        ? services.map((service) => {
            const actions = isAdminSpa ? `
                <td class="text-nowrap">
                    <button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="editSpaService(${Number(service.item_id)})">
                        <i class="fas fa-edit"></i> Sửa
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSpaService(${Number(service.item_id)})">
                        <i class="fas fa-trash"></i> Xóa
                    </button>
                </td>` : "";
            return `
                <tr>
                    <td><code>${escapeInventoryHtml(service.masanpham)}</code></td>
                    <td><strong>${escapeInventoryHtml(service.ten_sanpham || service.name)}</strong></td>
                    <td class="text-primary font-weight-bold">${formatCurrency(service.dongia)}</td>
                    ${actions}
                </tr>`;
        }).join("")
        : `<tr><td colspan="${isAdminSpa ? 4 : 3}" class="text-center text-muted">Chưa có dịch vụ Spa</td></tr>`;
}

function toggleMedicineDosageFields() {
    const isMedicine = document.getElementById("inventory_category")?.value === "THUOC";
    const fields = document.getElementById("medicineDosageFields");
    const dose = document.getElementById("inventory_dose");
    const mgUnit = document.getElementById("inventory_mg_unit");
    if (!fields || !dose || !mgUnit) return;

    fields.classList.toggle("d-none", !isMedicine);
    dose.required = isMedicine;
    mgUnit.required = isMedicine;
}

function openInventoryForm(item = null) {
    if (!isAdminInventory) return;
    document.getElementById("inventoryForm").reset();
    document.getElementById("inventory_form_id").value = item ? item.item_id : "";
    document.getElementById("inventoryFormTitle").innerText = item ? "Cập nhật mặt hàng kho" : "Thêm mặt hàng kho";
    document.getElementById("inventory_code").value = item?.masanpham || "";
    document.getElementById("inventory_name").value = item?.ten_sanpham || "";
    document.getElementById("inventory_category").value = item?.danhmuc || "THUOC";
    document.getElementById("inventory_unit").value = item?.donvitinh || "";
    document.getElementById("inventory_price").value = item?.dongia ?? "";
    document.getElementById("inventory_minimum").value = item?.tonkhotoithieu ?? 10;
    document.getElementById("inventory_dose").value = item?.lieu_mg_kg ?? "";
    document.getElementById("inventory_mg_unit").value = item?.mg_moi_don_vi ?? "";
    toggleMedicineDosageFields();
    $("#inventoryFormModal").modal("show");
}

function editInventoryItem(itemId) {
    if (!isAdminInventory) return;
    const item = inventoryItems.find((row) => Number(row.item_id) === Number(itemId));
    if (!item) {
        alert("Không tìm thấy mặt hàng.");
        return;
    }
    openInventoryForm(item);
}

async function saveInventoryItem(event) {
    event.preventDefault();
    if (!isAdminInventory) {
        alert("Chỉ quản trị viên được quản lý danh mục kho.");
        return;
    }

    const id = document.getElementById("inventory_form_id").value;
    const category = document.getElementById("inventory_category").value;
    const payload = {
        ten_sanpham: document.getElementById("inventory_name").value.trim(),
        danhmuc: category,
        donvitinh: document.getElementById("inventory_unit").value.trim(),
        dongia: Number(document.getElementById("inventory_price").value),
        tonkhotoithieu: Number(document.getElementById("inventory_minimum").value || 0),
        lieu_mg_kg: category === "THUOC" ? Number(document.getElementById("inventory_dose").value) : null,
        mg_moi_don_vi: category === "THUOC" ? Number(document.getElementById("inventory_mg_unit").value) : null,
    };

    const button = document.getElementById("btnSaveInventory");
    button.disabled = true;
    button.innerText = "Đang lưu...";
    try {
        await apiRequest(id ? `/admin/inventory/${id}` : "/admin/inventory", {
            method: id ? "PUT" : "POST",
            body: JSON.stringify(payload),
        });
        $("#inventoryFormModal").modal("hide");
        await loadInventory();
        alert(id ? "Đã cập nhật mặt hàng." : "Đã thêm mặt hàng vào kho.");
    } catch (error) {
        alert("Không thể lưu mặt hàng: " + error.message);
    } finally {
        button.disabled = false;
        button.innerText = "Lưu mặt hàng";
    }
}

async function deleteInventoryItem(itemId) {
    if (!isAdminInventory) return;
    const item = inventoryItems.find((row) => Number(row.item_id) === Number(itemId));
    if (!item) return;

    if (!confirm(`Bạn có chắc muốn xóa "${item.masanpham} - ${item.ten_sanpham}"?`)) return;
    try {
        await apiRequest(`/admin/inventory/${itemId}`, { method: "DELETE" });
        await loadInventory();
        alert("Đã xóa mặt hàng.");
    } catch (error) {
        alert("Không thể xóa mặt hàng: " + error.message);
    }
}


function openSpaServiceForm(service = null) {
    if (!isAdminSpa) return;
    document.getElementById("spaServiceForm").reset();
    document.getElementById("spa_service_form_id").value = service ? service.item_id : "";
    document.getElementById("spaServiceFormTitle").innerText = service ? "Cập nhật dịch vụ Spa" : "Thêm dịch vụ Spa";
    document.getElementById("spa_service_code").value = service?.masanpham || "";
    document.getElementById("spa_service_name").value = service?.ten_sanpham || "";
    document.getElementById("spa_service_unit").value = service?.donvitinh || "lần";
    document.getElementById("spa_service_price").value = service?.dongia ?? "";
    $("#spaServiceFormModal").modal("show");
}

function editSpaService(serviceId) {
    if (!isAdminSpa) return;
    const service = inventoryItems.find((item) =>
        Number(item.item_id) === Number(serviceId) && item.danhmuc === "DICH_VU_SPA"
    );
    if (!service) {
        alert("Không tìm thấy dịch vụ Spa.");
        return;
    }
    openSpaServiceForm(service);
}

async function saveSpaService(event) {
    event.preventDefault();
    if (!isAdminSpa) {
        alert("Chỉ quản trị viên được quản lý dịch vụ Spa.");
        return;
    }

    const id = document.getElementById("spa_service_form_id").value;
    const payload = {
        ten_sanpham: document.getElementById("spa_service_name").value.trim(),
        donvitinh: document.getElementById("spa_service_unit").value.trim(),
        dongia: Number(document.getElementById("spa_service_price").value),
    };

    const button = document.getElementById("btnSaveSpaService");
    button.disabled = true;
    button.innerText = "Đang lưu...";
    try {
        await apiRequest(id ? `/admin/spa-services/${id}` : "/admin/spa-services", {
            method: id ? "PUT" : "POST",
            body: JSON.stringify(payload),
        });
        $("#spaServiceFormModal").modal("hide");
        await loadInventory();
        alert(id ? "Đã cập nhật dịch vụ Spa." : "Đã thêm dịch vụ Spa.");
    } catch (error) {
        alert("Không thể lưu dịch vụ Spa: " + error.message);
    } finally {
        button.disabled = false;
        button.innerText = "Lưu dịch vụ";
    }
}

async function deleteSpaService(serviceId) {
    if (!isAdminSpa) return;
    const service = inventoryItems.find((item) =>
        Number(item.item_id) === Number(serviceId) && item.danhmuc === "DICH_VU_SPA"
    );
    if (!service) return;

    if (!confirm(`Bạn có chắc muốn xóa dịch vụ "${service.masanpham} - ${service.ten_sanpham}"?`)) return;
    try {
        await apiRequest(`/admin/spa-services/${serviceId}`, { method: "DELETE" });
        await loadInventory();
        alert("Đã xóa dịch vụ Spa.");
    } catch (error) {
        alert("Không thể xóa dịch vụ Spa: " + error.message);
    }
}
