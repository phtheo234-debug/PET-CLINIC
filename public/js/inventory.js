let inventoryItems = [];
let isAdminInventory = false;
let isAdminSpa = false;

document.addEventListener("DOMContentLoaded", async () => {
    await initPage();
    const user = getCurrentUser();
    isAdminInventory = !!user && user.vaitro === "QUAN_TRI";
    isAdminSpa = isAdminInventory;
    document.querySelectorAll(".inventoryLotColumn").forEach(el => {
        el.classList.toggle("d-none", !isAdminInventory);
    });

    if (isAdminInventory) {
        document.getElementById("btnAddSpaService")?.classList.remove("d-none");
        document.getElementById("spaActionHeader")?.classList.remove("d-none");
        document.querySelectorAll(".inventoryAddButton, .inventoryActionHeader").forEach(el => el.classList.remove("d-none"));
    }

    document.getElementById("inventoryForm")?.addEventListener("submit", saveInventoryItem);
    document.getElementById("spaServiceForm")?.addEventListener("submit", saveSpaService);
    document.getElementById("inventoryBatchForm")?.addEventListener("submit", saveInventoryBatch);
    document.getElementById("inventory_category")?.addEventListener("change", () => {
        if (!document.getElementById("inventory_form_id").value) {
            document.getElementById("inventory_code").value = generateInventoryItemCode(document.getElementById("inventory_category").value);
        }
        toggleInventoryFields(false);
    });
    document.getElementById("inventory_stock")?.addEventListener("input", () => toggleInventoryFields(false));
    document.getElementById("inventory_import_date")?.addEventListener("change", updateInitialDateLimits);
    document.getElementById("inventory_manufacture")?.addEventListener("change", updateInitialDateLimits);
    document.getElementById("batch_import_date")?.addEventListener("change", updateBatchDateLimits);
    document.getElementById("batch_manufacture_date")?.addEventListener("change", updateBatchDateLimits);
    toggleInventoryFields(false);
    await loadInventory();
});

async function loadInventory() {
    try {
        const res = await apiRequest("/inventory");
        inventoryItems = Array.isArray(res) ? res : (Array.isArray(res.data) ? res.data : []);
        renderInventory(inventoryItems);
    } catch (err) {
        ["medicalInventoryTableBody", "foodInventoryTableBody", "goodsInventoryTableBody"].forEach(id => {
            const body = document.getElementById(id);
            if (body) body.innerHTML = `<tr><td colspan="8" class="text-danger text-center">${escapeInventoryHtml(err.message || "Lỗi tải kho")}</td></tr>`;
        });
    }
}


function renderExpiryNotice(items) {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const limit = new Date(today);
    const originalDay = limit.getDate();
    limit.setMonth(limit.getMonth() + 6);
    if (limit.getDate() !== originalDay) limit.setDate(0);
    const soon = [];
    const expired = [];
    items.filter(item => ["THUOC", "VAC_XIN", "THUC_AN"].includes(item.danhmuc))
        .forEach(item => (item.batches || []).forEach(batch => {
            if (!batch.ngayhethan || Number(batch.soluong) <= 0 || !["DANG_SU_DUNG", "HET_HAN"].includes(batch.trangthai)) return;
            const date = new Date(batch.ngayhethan + "T00:00:00");
            const info = {name: item.ten_sanpham, lot: batch.solo, qty: batch.soluong, date: batch.ngayhethan};
            if (date < today) expired.push(info);
            else if (date <= limit) soon.push(info);
        }));
    const box = document.getElementById("inventoryExpiryNotice");
    if (!box) return;
    if (!soon.length && !expired.length) {
        box.innerHTML = '<div class="alert alert-success mb-0">Không có lô sắp hết hạn trong 6 tháng tới.</div>';
        return;
    }
    const rows = list => list.slice(0, 8).map(item =>
        '<li><strong>' + escapeInventoryHtml(item.name) + '</strong>' +
        (isAdminInventory ? ' · Lô ' + escapeInventoryHtml(item.lot) : '') +
        ' · Tồn ' + Number(item.qty) + ' · HSD ' + escapeInventoryHtml(item.date) + '</li>'
    ).join("");
    box.innerHTML =
        (expired.length ? '<div class="alert alert-danger"><strong>' + expired.length + ' lô đã hết hạn — đã tự khóa, không thể xuất/bán.</strong><ul class="mb-0 mt-2">' + rows(expired) + '</ul></div>' : '') +
        (soon.length ? '<div class="alert alert-warning"><strong>' + soon.length + ' lô còn hạn dưới 6 tháng.</strong><ul class="mb-0 mt-2">' + rows(soon) + '</ul></div>' : '');
}

function escapeInventoryHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (char) => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
    })[char]);
}

function renderInventory(items) {
    const today = new Date().toLocaleDateString("en-CA");
    const categories = {
        THUOC: "Thuốc", VAC_XIN: "Vắc-xin", THUC_AN: "Thức ăn", DO_DUNG: "Đồ dùng"
    };
    renderExpiryNotice(items.filter(item => ["THUOC", "VAC_XIN", "THUC_AN"].includes(item.danhmuc)));

    const renderBatchTable = (rows, bodyId) => {
        const body = document.getElementById(bodyId);
        body.innerHTML = rows.length ? rows.map(item => {
            const usable = (item.batches || []).filter(batch =>
                batch.trangthai === "DANG_SU_DUNG" && Number(batch.soluong) > 0 &&
                batch.ngayhethan && batch.ngayhethan >= today
            );
            const expired = (item.batches || []).some(batch =>
                batch.trangthai === "HET_HAN" && Number(batch.soluong) > 0
            );
            const batch = usable[0] || (item.batches || []).find(b => Number(b.soluong) > 0) || {};
            const quantity = Number(item.tong_ton_kho || 0);
            const status = quantity > 0
                ? '<span class="badge badge-success">Còn hàng</span>'
                : expired
                    ? '<span class="badge badge-danger">Có lô hết hạn đã khóa</span>'
                    : '<span class="badge badge-secondary">Hết hàng</span>';
            const actions = isAdminInventory ? '<td class="text-nowrap">' +
                '<button type="button" class="btn btn-sm btn-outline-success mr-1" onclick="openInventoryBatchForm(' + Number(item.item_id) + ')">Nhập lô</button>' +
                '<button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="editInventoryItem(' + Number(item.item_id) + ')">Sửa</button>' +
                '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteInventoryItem(' + Number(item.item_id) + ')">Xóa</button></td>' : '';
            return '<tr>' +
                '<td><code>' + escapeInventoryHtml(item.masanpham) + '</code></td>' +
                '<td><strong>' + escapeInventoryHtml(item.ten_sanpham) + '</strong></td>' +
                '<td class="inventoryLotColumn' + (isAdminInventory ? '' : ' d-none') + '">' + escapeInventoryHtml(batch.solo || '—') + '</td>' +
                '<td>' + quantity + '</td>' +
                '<td>' + escapeInventoryHtml(batch.ngaysanxuat || '—') + '</td>' +
                '<td>' + escapeInventoryHtml(batch.ngayhethan || '—') + '</td>' +
                '<td>' + formatCurrency(item.dongia) + '</td><td>' + status + '</td>' + actions +
                '</tr>';
        }).join("") : '<tr><td colspan="' + (isAdminInventory ? 9 : 8) + '" class="text-center text-muted">Chưa có mặt hàng.</td></tr>';
    };

    renderBatchTable(items.filter(item => ["THUOC", "VAC_XIN"].includes(item.danhmuc)), "medicalInventoryTableBody");
    renderBatchTable(items.filter(item => item.danhmuc === "THUC_AN"), "foodInventoryTableBody");

    const goods = items.filter(item => item.danhmuc === "DO_DUNG");
    document.getElementById("goodsInventoryTableBody").innerHTML = goods.length ? goods.map(item => {
        const actions = isAdminInventory ? '<td class="text-nowrap">' +
            '<button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="editInventoryItem(' + Number(item.item_id) + ')">Sửa / cập nhật tồn</button>' +
            '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteInventoryItem(' + Number(item.item_id) + ')">Xóa</button></td>' : '';
        return '<tr><td><code>' + escapeInventoryHtml(item.masanpham) + '</code></td>' +
            '<td><strong>' + escapeInventoryHtml(item.ten_sanpham) + '</strong></td>' +
            '<td>' + Number(item.soluongton ?? item.tong_ton_kho ?? 0) + '</td>' +
            '<td>' + formatCurrency(item.dongia) + '</td>' + actions + '</tr>';
    }).join("") : '<tr><td colspan="' + (isAdminInventory ? 5 : 4) + '" class="text-center text-muted">Chưa có đồ dùng.</td></tr>';

    const services = items.filter(item => item.danhmuc === "DICH_VU_SPA");
    document.getElementById("spaTableBody").innerHTML = services.length ? services.map(service => {
        const actions = isAdminSpa ? '<td class="text-nowrap">' +
            '<button type="button" class="btn btn-sm btn-outline-primary mr-1" onclick="editSpaService(' + Number(service.item_id) + ')">Sửa</button>' +
            '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSpaService(' + Number(service.item_id) + ')">Xóa</button></td>' : '';
        return '<tr><td><code>' + escapeInventoryHtml(service.masanpham) + '</code></td>' +
            '<td><strong>' + escapeInventoryHtml(service.ten_sanpham) + '</strong></td>' +
            '<td class="text-primary font-weight-bold">' + formatCurrency(service.dongia) + '</td>' + actions + '</tr>';
    }).join("") : '<tr><td colspan="' + (isAdminSpa ? 4 : 3) + '" class="text-center text-muted">Chưa có dịch vụ.</td></tr>';
}

function localInventoryDate() {
    const now = new Date();
    return new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
}

function generateInventoryLotCode() {
    const date = localInventoryDate().replaceAll("-", "");
    const suffix = String(Math.floor(Math.random() * 1000000)).padStart(6, "0");
    return "LO-" + date + "-" + suffix;
}

function generateInventoryItemCode(category) {
    const prefixes = {THUOC:"THUOC", VAC_XIN:"VACXIN", THUC_AN:"THUCAN", DO_DUNG:"DODUNG"};
    const prefix = prefixes[category] || "SP";
    const date = localInventoryDate().replaceAll("-", "");
    const suffix = String(Math.floor(Math.random() * 1000000)).padStart(6, "0");
    return prefix + "-" + date + "-" + suffix;
}

function generateSpaServiceCode() {
    const date = localInventoryDate().replaceAll("-", "");
    const suffix = String(Math.floor(Math.random() * 1000000)).padStart(6, "0");
    return "SPA-" + date + "-" + suffix;
}

function updateInitialDateLimits() {
    const importDate = document.getElementById("inventory_import_date")?.value || localInventoryDate();
    const importInput = document.getElementById("inventory_import_date");
    const manufacture = document.getElementById("inventory_manufacture");
    const expiry = document.getElementById("inventory_expiry");
    if (importInput) importInput.max = localInventoryDate();
    if (manufacture) manufacture.max = importDate;
    if (expiry) expiry.min = manufacture?.value || importDate;
}

function updateBatchDateLimits() {
    const importDate = document.getElementById("batch_import_date")?.value || localInventoryDate();
    const manufacture = document.getElementById("batch_manufacture_date");
    const expiry = document.getElementById("batch_expiry_date");
    if (manufacture) manufacture.max = importDate;
    if (expiry) expiry.min = manufacture?.value || importDate;
}

function openInventoryBatchForm(itemId) {
    if (!isAdminInventory) return;
    const item = inventoryItems.find((row) => Number(row.item_id) === Number(itemId));
    if (!item) { alert("Không tìm thấy mặt hàng."); return; }
    const today = new Date();
    const localToday = new Date(today.getTime() - today.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    document.getElementById("inventoryBatchForm").reset();
    document.getElementById("batch_item_id").value = item.item_id;
    document.getElementById("batch_item_label").innerText = item.masanpham + " · " + item.ten_sanpham + " (" + item.donvitinh + ")";
    document.getElementById("batch_import_date").value = localToday;
    document.getElementById("batch_import_date").max = localToday;
    document.getElementById("batch_lot_preview").value = generateInventoryLotCode();
    const manufactureInput = document.getElementById("batch_manufacture_date");
    const expiryInput = document.getElementById("batch_expiry_date");
    manufactureInput.value = localToday;
    manufactureInput.max = localToday;
    expiryInput.min = localToday;
    updateBatchDateLimits();
    $("#inventoryBatchModal").modal("show");
}

async function saveInventoryBatch(event) {
    event.preventDefault();
    if (!isAdminInventory) return;
    const payload = {
        item_id: Number(document.getElementById("batch_item_id").value),
        solo: document.getElementById("batch_lot_preview").value,
        soluong: Number(document.getElementById("batch_quantity").value),
        ngaynhap: document.getElementById("batch_import_date").value,
        ngaysanxuat: document.getElementById("batch_manufacture_date").value,
        ngayhethan: document.getElementById("batch_expiry_date").value || null,
    };
    if (!Number.isInteger(payload.soluong) || payload.soluong < 1) {
        alert("Số lượng nhập không hợp lệ."); return;
    }
    if (!payload.ngaysanxuat || !payload.ngayhethan) {
        alert("Vui lòng nhập ngày sản xuất và hạn sử dụng."); return;
    }
    if (payload.ngaysanxuat > payload.ngaynhap) {
        alert("Ngày sản xuất không được sau ngày nhập."); return;
    }
    if (payload.ngayhethan <= payload.ngaysanxuat || payload.ngayhethan <= payload.ngaynhap) {
        alert("Hạn sử dụng phải sau ngày sản xuất và ngày nhập."); return;
    }
    const button = document.getElementById("btnSaveInventoryBatch");
    button.disabled = true;
    button.innerText = "Đang nhập...";
    try {
        await apiRequest("/inventory/batch/import", {method:"POST", body:JSON.stringify(payload)});
        $("#inventoryBatchModal").modal("hide");
        await loadInventory();
        alert("Đã nhập lô hàng. Tồn kho được cập nhật theo lô.");
    } catch (error) {
        alert("Không thể nhập lô: " + error.message);
    } finally {
        button.disabled = false;
        button.innerText = "Xác nhận nhập lô";
    }
}

function toggleInventoryFields(isEditing = false) {
    const category = document.getElementById("inventory_category")?.value;
    const isMedical = ["THUOC", "VAC_XIN"].includes(category);
    const isGoods = category === "DO_DUNG";
    const hasBatch = ["THUOC", "VAC_XIN", "THUC_AN"].includes(category);
    const categoryGroup = document.getElementById("inventoryCategoryGroup");
    const categorySelect = document.getElementById("inventory_category");
    if (categorySelect) Array.from(categorySelect.options).forEach(option => {
        if (["THUC_AN", "DO_DUNG"].includes(option.value)) option.hidden = true;
    });
    const unitGroup = document.getElementById("inventoryUnitGroup");
    const expiryGroup = document.getElementById("inventoryExpiryGroup");
    const importGroup = document.getElementById("inventoryImportGroup");
    const manufactureGroup = document.getElementById("inventoryManufactureGroup");
    const batchFields = document.getElementById("inventoryBatchFields");
    const unit = document.getElementById("inventory_unit");
    const stock = document.getElementById("inventory_stock");
    const stockLabel = document.getElementById("inventoryStockLabel");
    const stockHelp = document.getElementById("inventoryStockHelp");
    if (categoryGroup) categoryGroup.classList.toggle("d-none", !isMedical);
    if (unitGroup) unitGroup.classList.toggle("d-none", isGoods);
    if (unit) unit.required = !isGoods;
    if (importGroup) importGroup.classList.toggle("d-none", !hasBatch || isEditing);
    if (expiryGroup) expiryGroup.classList.toggle("d-none", !hasBatch || isEditing);
    if (manufactureGroup) manufactureGroup.classList.toggle("d-none", !hasBatch || isEditing);
    if (batchFields) batchFields.classList.toggle("d-none", !hasBatch || isEditing);
    if (stockLabel) stockLabel.textContent = isEditing && hasBatch ? "Tồn kho hiện tại" : "Số lượng";
    if (stockHelp) stockHelp.textContent = isEditing && hasBatch
        ? "Tồn kho được quản lý theo từng lô; dùng chức năng Nhập lô để bổ sung."
        : (hasBatch ? "Số lượng này sẽ được lưu thành một lô nhập ban đầu, mã lô tự động sinh." : "Số lượng ban đầu được lưu trực tiếp vào kho.");
    if (stock) {
        stock.readOnly = isEditing && hasBatch;
        if (isEditing && hasBatch) {
            const item = inventoryItems.find(row => Number(row.item_id) === Number(document.getElementById("inventory_form_id").value));
            stock.value = Number(item?.tong_ton_kho ?? 0);
        }
    }
    const importDate = document.getElementById("inventory_import_date");
    const manufacture = document.getElementById("inventory_manufacture");
    const expiry = document.getElementById("inventory_expiry");
    const datesRequired = hasBatch && !isEditing && Number(stock?.value || 0) > 0;
    if (importDate) importDate.required = datesRequired;
    if (manufacture) manufacture.required = datesRequired;
    if (expiry) expiry.required = datesRequired;
    updateInitialDateLimits();
}

function openInventoryForm(item = null, category = null) {
    if (!isAdminInventory) return;
    document.getElementById("inventoryForm").reset();
    document.getElementById("inventory_form_id").value = item ? item.item_id : "";
    document.getElementById("inventoryFormTitle").innerText = item ? "Cập nhật mặt hàng kho" : "Thêm mặt hàng kho";
    document.getElementById("inventory_category").value = item?.danhmuc || category || "THUOC";
    document.getElementById("inventory_code").value = item?.masanpham || generateInventoryItemCode(document.getElementById("inventory_category").value);
    document.getElementById("inventory_name").value = item?.ten_sanpham || "";
    document.getElementById("inventory_unit").value = item?.donvitinh || "";
    document.getElementById("inventory_price").value = item ? (Number(item.dongia || 0) / 1000) : "";
    document.getElementById("inventory_stock").value = item
        ? (item.danhmuc === "DO_DUNG" ? Number(item.soluongton || 0) : Number(item.tong_ton_kho || 0))
        : 0;
    if (!item && ["THUOC", "VAC_XIN", "THUC_AN"].includes(category || "THUOC")) {
        document.getElementById("inventory_lot_preview").value = generateInventoryLotCode();
        document.getElementById("inventory_import_date").value = localInventoryDate();
        document.getElementById("inventory_manufacture").value = localInventoryDate();
    }
    toggleInventoryFields(!!item);
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
    const isBatchCategory = ["THUOC", "VAC_XIN", "THUC_AN"].includes(category);
    const quantity = Number(document.getElementById("inventory_stock").value || 0);
    const importDate = document.getElementById("inventory_import_date").value;
    const expiry = document.getElementById("inventory_expiry").value;
    const manufacture = document.getElementById("inventory_manufacture").value;
    const lotCode = document.getElementById("inventory_lot_preview").value;
    const todayDate = new Date();
    const localToday = new Date(todayDate.getTime() - todayDate.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
    if (!id && isBatchCategory && quantity > 0 && (!importDate || !manufacture || !expiry)) {
        alert("Vui lòng nhập ngày nhập, ngày sản xuất và hạn sử dụng cho lô ban đầu.");
        return;
    }
    if (!id && isBatchCategory && quantity > 0 && importDate > localToday) {
        alert("Ngày nhập không được sau ngày hiện tại.");
        return;
    }
    if (!id && isBatchCategory && quantity > 0 && manufacture > importDate) {
        alert("Ngày sản xuất không được sau ngày nhập.");
        return;
    }
    if (!id && isBatchCategory && quantity > 0 && (expiry <= manufacture || expiry <= importDate)) {
        alert("Hạn sử dụng phải sau ngày sản xuất và ngày nhập.");
        return;
    }
    const payload = {
        masanpham: document.getElementById("inventory_code").value,
        ten_sanpham: document.getElementById("inventory_name").value.trim(),
        danhmuc: category,
        donvitinh: category === "DO_DUNG" ? "" : document.getElementById("inventory_unit").value.trim(),
        soluong: quantity,
        dongia: Number(document.getElementById("inventory_price").value) * 1000,
        lieu_mg_kg: null,
        mg_moi_don_vi: null,
    };
    if (!id && isBatchCategory) {
        payload.ngaynhap = importDate || localToday;
        payload.ngaysanxuat = manufacture || null;
        payload.ngayhethan = expiry || null;
        payload.solo = lotCode;
    }
    if (id && category === "DO_DUNG") {
        payload.soluong = quantity;
    } else if (id) {
        delete payload.soluong;
    }

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
    document.getElementById("spa_service_code").value = service?.masanpham || generateSpaServiceCode();
    document.getElementById("spa_service_name").value = service?.ten_sanpham || "";
    document.getElementById("spa_service_price").value = service ? (Number(service.dongia || 0) / 1000) : "";
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
        masanpham: document.getElementById("spa_service_code").value,
        ten_sanpham: document.getElementById("spa_service_name").value.trim(),
        dongia: Number(document.getElementById("spa_service_price").value) * 1000,
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
