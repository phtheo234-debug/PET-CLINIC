
let retailPosItems = [];

async function loadRetailPosOptions() {
    const petSelect = document.getElementById("pos_pet_select");
    try {
        const [petsResponse, inventoryResponse] = await Promise.all([apiRequest("/pets"), apiRequest("/inventory")]);
        const pets = extractList(petsResponse);
        const items = extractList(inventoryResponse);
        petSelect.innerHTML = '<option value="">-- Chọn thú cưng --</option>' + pets.map(pet => {
            const id = Number(pet.pet_id ?? pet.id);
            return '<option value="' + id + '">' + escapeInvoiceHtml(pet.mathucung || ("PET-" + id)) +
                ' - ' + escapeInvoiceHtml(pet.ten_thucung || pet.name || "") + '</option>';
        }).join("");
        retailPosItems = items.filter(item => ["THUC_AN", "DO_DUNG", "DICH_VU_SPA"].includes(item.danhmuc));
        document.getElementById("posCartRows").replaceChildren();
        addRetailPosLine();
    } catch (error) {
        petSelect.innerHTML = '<option value="">Không tải được hồ sơ thú cưng</option>';
        document.getElementById("retailPosResult").textContent = error.message;
    }
}

async function updateRetailPosOwner() {
    const petId = Number(document.getElementById("pos_pet_select").value);
    const ownerInput = document.getElementById("pos_owner_name");
    if (!petId) { ownerInput.value = ""; return; }
    try {
        const response = await apiRequest("/pets/" + petId);
        const pet = response?.data?.data || response?.data || {};
        ownerInput.value = pet.owner?.name || pet.owner?.ho_ten || pet.owner_name || "";
    } catch (_) { ownerInput.value = ""; }
}

function addRetailPosLine() {
    const container = document.getElementById("posCartRows");
    if (!container) return;
    const row = document.createElement("div");
    row.className = "form-row align-items-end pos-cart-line border rounded p-2 mb-2";
    const options = retailPosItems.map(item => {
        const category = item.danhmuc === "THUC_AN" ? "Thức ăn" : item.danhmuc === "DO_DUNG" ? "Đồ dùng" : "Dịch vụ";
        return '<option value="' + Number(item.item_id) + '">' + category + ' · ' +
            escapeInvoiceHtml(item.ten_sanpham) + ' · ' + formatCurrency(item.dongia) + '</option>';
    }).join("");
    row.innerHTML = '<div class="form-group col-md-8 mb-1"><label>Mặt hàng / dịch vụ</label><select class="form-control pos-line-item" required><option value="">-- Chọn --</option>' + options + '</select></div>' +
        '<div class="form-group col-md-2 mb-1"><label>Số lượng</label><input type="number" class="form-control pos-line-quantity" min="1" step="1" value="1" required></div>' +
        '<div class="form-group col-md-2 mb-1"><button type="button" class="btn btn-outline-danger btn-block btn-remove-pos-line">Xóa</button></div>';
    row.querySelector(".btn-remove-pos-line").addEventListener("click", () => {
        row.remove();
        if (!container.children.length) addRetailPosLine();
    });
    container.appendChild(row);
}

async function submitRetailPos(event) {
    event.preventDefault();
    const petId = Number(document.getElementById("pos_pet_select").value);
    const result = document.getElementById("retailPosResult");
    const button = document.getElementById("btnRetailPosCheckout");
    const lines = Array.from(document.querySelectorAll(".pos-cart-line")).map(row => ({
        item_id: Number(row.querySelector(".pos-line-item").value),
        quantity: Number(row.querySelector(".pos-line-quantity").value)
    }));
    if (!petId) { alert("Vui lòng chọn hồ sơ thú cưng."); return; }
    if (!lines.length || lines.some(line => !line.item_id || !Number.isInteger(line.quantity) || line.quantity < 1)) {
        alert("Vui lòng chọn mặt hàng và số lượng hợp lệ."); return;
    }
    button.disabled = true;
    try {
        const response = await apiRequest("/payment/pos/checkout", {
            method: "POST",
            body: JSON.stringify({
                pet_id: petId,
                payment_method: document.getElementById("pos_payment_method").value,
                items: lines
            })
        });
        const data = response?.data?.data || response?.data || {};
        result.innerHTML = '<div class="alert alert-success">Thanh toán thành công hóa đơn <strong>' +
            escapeInvoiceHtml(data.mahoadon || "") + '</strong> · Tổng tiền: <strong>' +
            formatCurrency(data.tong_thanhtoan) + '</strong> <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="printInvoice(' +
            Number(data.invoice_id) + ')">In hóa đơn</button></div>';
        await Promise.all([loadInvoices(), loadRetailPosOptions()]);
    } catch (error) {
        result.innerHTML = '<div class="alert alert-danger">Không thể thanh toán: ' + escapeInvoiceHtml(error.message) + '</div>';
    } finally { button.disabled = false; }
}

async function loadPendingOutpatientVisits() {
    const select = document.getElementById("outpatient_record_select");
    if (!select) return;
    try {
        const response = await apiRequest("/payment/visits/pending");
        const visits = extractList(response);
        select.innerHTML = '<option value="">-- Chọn phiếu khám ngoại trú --</option>' + visits.map((visit) =>
            '<option value="' + Number(visit.record_id) + '">#' + Number(visit.record_id) + " · " + escapeInvoiceHtml(visit.mathucung || "") + " - " + escapeInvoiceHtml(visit.ten_thucung || "") + " · " + escapeInvoiceHtml(visit.ngaykham || "") + "</option>"
        ).join("");
        if (!visits.length) select.innerHTML = '<option value="">Không có phiếu khám ngoại trú chờ thanh toán</option>';
    } catch (error) { select.innerHTML = '<option value="">Không tải được phiếu khám</option>'; }
}

async function payOutpatientVisit(event) {
    event.preventDefault();
    const recordId = Number(document.getElementById("outpatient_record_select").value);
    const button = document.getElementById("btnPayOutpatient");
    const result = document.getElementById("outpatientPaymentResult");
    if (!recordId) { alert("Vui lòng chọn phiếu khám cần thanh toán."); return; }
    button.disabled = true;
    try {
        const response = await apiRequest("/payment/visits/checkout", {method:"POST", body:JSON.stringify({record_id:recordId, payment_method:document.getElementById("outpatient_payment_method").value})});
        const data = response?.data?.data || response?.data || {};
        result.innerHTML = '<div class="alert alert-success mb-0">Đã thanh toán phiếu khám <b>' + escapeInvoiceHtml(data.mahoadon || "") + '</b>. Phí khám: <b>' + formatCurrency(data.tien_kham) + '</b>; thuốc/vắc-xin: <b>' + formatCurrency(data.tien_thuoc) + '</b>; tổng cộng: <b>' + formatCurrency(data.tong_thanhtoan) + '</b>. <button type="button" class="btn btn-sm btn-outline-primary ml-2" onclick="printInvoice(' + Number(data.invoice_id) + ')"><i class="fas fa-print mr-1"></i>In hóa đơn</button></div>';
        await Promise.all([loadPendingOutpatientVisits(), loadInvoices()]);
    } catch (error) { result.innerHTML = '<div class="alert alert-danger mb-0">Không thanh toán được: ' + escapeInvoiceHtml(error.message) + '</div>'; }
    finally { button.disabled = false; }
}
async function printInvoice(invoiceId) {
    try {
        const response = await apiRequest("/payment/invoices/" + invoiceId);
        const invoice = response?.data?.data || response?.data || {};
        const details = invoice.chi_tiet || [];
        const labels = {THUOC:"Thuốc",VAC_XIN:"Vắc-xin",THUC_AN:"Thức ăn",DO_DUNG:"Đồ dùng",PHI_KHAM:"Phí khám",LUU_TRU:"Lưu trú",DICH_VU_SPA:"Dịch vụ"};
        const rows = details.map((line,index) => "<tr><td>"+(index+1)+"</td><td>"+escapeInvoiceHtml(line.ten_sanpham)+"</td><td>"+(labels[line.loai_chiphi] || escapeInvoiceHtml(line.loai_chiphi))+"</td><td>"+Number(line.soluong)+" "+escapeInvoiceHtml(line.donvitinh || "")+"</td><td>"+formatCurrency(line.dongia)+"</td><td>"+formatCurrency(line.thanhtien)+"</td></tr>").join("");
        const win = window.open("", "_blank", "width=900,height=750");
        if (!win) { alert("Trình duyệt đang chặn cửa sổ in. Hãy cho phép popup rồi thử lại."); return; }
        win.document.write("<!DOCTYPE html><html lang=\"vi\"><head><meta charset=\"UTF-8\"><title>Hóa đơn</title><style>body{font-family:Arial;padding:28px;color:#222}h2,h3{text-align:center}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #777;padding:8px;text-align:left}.total{text-align:right;font-size:18px;font-weight:bold;margin-top:20px}.sign{display:flex;justify-content:space-between;margin-top:55px}.sign div{width:40%;text-align:center}</style></head><body>" +
            "<h2>PET CLINIC</h2><h3>HÓA ĐƠN THANH TOÁN</h3>" +
            "<p><b>Mã hóa đơn:</b> "+escapeInvoiceHtml(invoice.mahoadon || "")+"</p><p><b>Ngày thanh toán:</b> "+escapeInvoiceHtml(invoice.ngaytao || "")+"</p>" +
            "<p><b>Thú cưng:</b> "+escapeInvoiceHtml(invoice.ten_thucung || "")+" ("+escapeInvoiceHtml(invoice.mathucung || "")+")</p><p><b>Chủ nuôi:</b> "+escapeInvoiceHtml(invoice.ten_chu_nuoi || "")+" &nbsp; <b>Điện thoại:</b> "+escapeInvoiceHtml(invoice.phone || "")+"</p>" +
            "<p><b>Phương thức:</b> "+escapeInvoiceHtml(invoice.phuongthucthanhtoan || "")+"</p><table><thead><tr><th>STT</th><th>Nội dung</th><th>Loại chi phí</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead><tbody>"+rows+"</tbody></table>" +
            "<div class=\"total\">TỔNG CỘNG: "+formatCurrency(invoice.tongtien)+"</div><div class=\"sign\"><div>Nhân viên thanh toán<br><br><br>........................</div><div>Chủ nuôi<br><br><br>........................</div></div></body></html>");
        win.document.close();
    } catch (error) { alert("Không thể tải hóa đơn để in: " + error.message); }
}
// Các endpoint tương ứng routes/api.php
const invoiceListEndpoint = "/payment/invoices";
const checkoutEndpoint = "/payment/checkout";

function extractList(res) {
    if (Array.isArray(res)) return res;
    if (res && Array.isArray(res.data)) return res.data;
    if (res && res.data && Array.isArray(res.data.data)) return res.data.data;
    return [];
}

function escapeInvoiceHtml(value) {
    return String(value ?? "").replace(/[&<>"']/g, (char) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
    })[char]);
}

function formatCurrency(value) {
    return new Intl.NumberFormat("vi-VN", {
        maximumFractionDigits: 0,
    }).format(Number(value) || 0) + " đ";
}

document.addEventListener("DOMContentLoaded", async () => {
    initPage();
    await Promise.all([loadPetsForInvoice(), loadInvoices(), loadSpaUsageOptions(), loadSpaUsages(), loadPendingOutpatientVisits(), loadRetailPosOptions()]);

    const petSelect = document.getElementById("pet_select");
    if (petSelect) {
        petSelect.addEventListener("change", updateSelectedOwner);
    }

    document.getElementById("spa_usage_service")?.addEventListener("change", updateSpaServicePrice);
    document.getElementById("pos_pet_select")?.addEventListener("change", updateRetailPosOwner);
    document.getElementById("btnAddPosLine")?.addEventListener("click", addRetailPosLine);
    document.getElementById("retailPosForm")?.addEventListener("submit", submitRetailPos);
    document.getElementById("outpatientVisitForm")?.addEventListener("submit", payOutpatientVisit);
    document.getElementById("spaUsageForm")?.addEventListener("submit", saveSpaUsage);

    const form = document.getElementById("invoiceForm");
    if (!form) return;

    form.addEventListener("submit", async (e) => {
        e.preventDefault();

        const petId = Number(document.getElementById("pet_select").value);
        const paymentMethod = document.getElementById("phuongthucthanhtoan").value;
        const resultBox = document.getElementById("checkoutResult");
        const submitBtn = document.getElementById("btnCheckout");

        if (!petId) {
            alert("Vui lòng chọn thú cưng đang nội trú!");
            document.getElementById("pet_select").focus();
            return;
        }

        // Payload chỉ gửi đúng hai trường Backend yêu cầu.
        const payload = {
            pet_id: petId,
            payment_method: paymentMethod,
        };

        const originalButton = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Đang thanh toán...';
        resultBox.innerHTML = "";

        try {
            const response = await apiRequest(checkoutEndpoint, {
                method: "POST",
                body: JSON.stringify(payload),
            });

            const detail = response?.data?.data || response?.data || {};
            resultBox.innerHTML = `
                <div class="alert alert-success mb-0">
                    <strong><i class="fas fa-check-circle mr-1"></i> ${escapeInvoiceHtml(response?.message || "Thanh toán thành công!")}</strong>
                    <hr>
                    <div>Mã hóa đơn: <strong>${escapeInvoiceHtml(detail.mahoadon || "-")}</strong></div>
                    <div>Phí khám (${Number(detail.so_lan_kham || 0)} lần): <strong>${formatCurrency(detail.tien_kham)}</strong></div>
                    <div>Tiền thuốc/vắc-xin: <strong>${formatCurrency(detail.tien_thuoc)}</strong></div>
                    <div>Phí lưu trú: <strong>${formatCurrency(detail.tien_long)}</strong></div>
                    <div>Phí dịch vụ Spa: <strong>${formatCurrency(detail.tien_spa)}</strong></div>
                    <div class="mt-1">Tổng thanh toán: <strong class="text-danger">${formatCurrency(detail.tong_thanhtoan)}</strong></div>
                    <small class="d-block mt-2">Thú cưng đã được xuất viện và lồng lưu trú đã được giải phóng.</small>
                </div>
            `;

            form.reset();
            await Promise.all([loadPetsForInvoice(), loadInvoices(), loadSpaUsages()]);
        } catch (err) {
            resultBox.innerHTML = `
                <div class="alert alert-danger mb-0">
                    <strong>Không thể thanh toán:</strong> ${escapeInvoiceHtml(err.message)}
                </div>
            `;
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalButton;
        }
    });
});

// Chỉ đưa thú cưng nội trú vào danh sách có thể thanh toán.
async function loadPetsForInvoice() {
    const petSelect = document.getElementById("pet_select");
    const ownerInput = document.getElementById("inv_owner");

    try {
        const response = await apiRequest("/pets");
        const pets = extractList(response);
        const inpatientPets = pets.filter((pet) => pet.trangthai === "NOI_TRU");

        if (!inpatientPets.length) {
            petSelect.innerHTML = '<option value="">-- Không có thú cưng đang nội trú --</option>';
            ownerInput.value = "";
            return;
        }

        petSelect.innerHTML = '<option value="">-- Chọn thú cưng cần thanh toán --</option>' +
            inpatientPets.map((pet) => {
                const id = pet.pet_id ?? pet.id;
                const code = escapeInvoiceHtml(pet.mathucung || `PET-${id}`);
                const name = escapeInvoiceHtml(pet.ten_thucung || pet.name || "");
                return `<option value="${id}">${code} - ${name}</option>`;
            }).join("");

        updateSelectedOwner();
    } catch (err) {
        petSelect.innerHTML = `<option value="">Lỗi tải thú cưng: ${escapeInvoiceHtml(err.message)}</option>`;
        ownerInput.value = "";
    }
}

function updateSelectedOwner() {
    const petSelect = document.getElementById("pet_select");
    const ownerInput = document.getElementById("inv_owner");
    const selectedOption = petSelect.options[petSelect.selectedIndex];
    const petId = Number(petSelect.value);

    if (!petId || !selectedOption) {
        ownerInput.value = "";
        return;
    }

    // Tải thông tin chi tiết để lấy chủ nuôi theo dữ liệu API.
    apiRequest(`/pets/${petId}`)
        .then((response) => {
            const pet = response?.data?.data || response?.data || {};
            ownerInput.value = pet.owner?.name || pet.owner_name || "";
        })
        .catch(() => {
            ownerInput.value = "";
        });
}


let spaServiceOptions = [];

async function loadSpaUsageOptions() {
    const petSelect = document.getElementById("spa_usage_pet");
    const serviceSelect = document.getElementById("spa_usage_service");
    try {
        const [petsResponse, inventoryResponse] = await Promise.all([
            apiRequest("/pets"),
            apiRequest("/inventory")
        ]);
        const pets = extractList(petsResponse);
        const items = extractList(inventoryResponse);
        petSelect.innerHTML = '<option value="">-- Chọn thú cưng --</option>' +
            pets.map((pet) => {
                const id = pet.pet_id ?? pet.id;
                return `<option value="${Number(id)}">${escapeInvoiceHtml(pet.mathucung || `PET-${id}`)} - ${escapeInvoiceHtml(pet.ten_thucung || pet.name || "")}</option>`;
            }).join("");

        spaServiceOptions = items.filter((item) => item.danhmuc === "DICH_VU_SPA");
        serviceSelect.innerHTML = '<option value="">-- Chọn dịch vụ --</option>' +
            spaServiceOptions.map((service) =>
                `<option value="${Number(service.item_id)}">${escapeInvoiceHtml(service.masanpham)} - ${escapeInvoiceHtml(service.ten_sanpham)}</option>`
            ).join("");
        updateSpaServicePrice();
    } catch (error) {
        petSelect.innerHTML = '<option value="">Không tải được danh sách thú cưng</option>';
        serviceSelect.innerHTML = '<option value="">Không tải được danh sách dịch vụ</option>';
        document.getElementById("spaUsageResult").innerHTML =
            `<div class="alert alert-danger">${escapeInvoiceHtml(error.message)}</div>`;
    }
}

function updateSpaServicePrice() {
    const id = Number(document.getElementById("spa_usage_service")?.value);
    const service = spaServiceOptions.find((item) => Number(item.item_id) === id);
    document.getElementById("spa_usage_price").value = service ? formatCurrency(service.dongia) : "";
}

async function saveSpaUsage(event) {
    event.preventDefault();
    const petId = Number(document.getElementById("spa_usage_pet").value);
    const itemId = Number(document.getElementById("spa_usage_service").value);
    const quantity = Number(document.getElementById("spa_usage_quantity").value);
    const button = document.getElementById("btnSaveSpaUsage");
    const result = document.getElementById("spaUsageResult");

    if (!petId || !itemId || !Number.isInteger(quantity) || quantity < 1) {
        alert("Vui lòng chọn thú cưng, dịch vụ và số lần sử dụng hợp lệ.");
        return;
    }

    button.disabled = true;
    try {
        const response = await apiRequest("/spa/usages", {
            method: "POST",
            body: JSON.stringify({
                pet_id: petId,
                item_id: itemId,
                soluong: quantity,
                ghichu: document.getElementById("spa_usage_note").value.trim()
            })
        });
        result.innerHTML = `<div class="alert alert-success mb-0">${escapeInvoiceHtml(response?.message || "Đã ghi nhận dịch vụ Spa.")}</div>`;
        document.getElementById("spaUsageForm").reset();
        document.getElementById("spa_usage_quantity").value = 1;
        updateSpaServicePrice();
        await loadSpaUsages();
    } catch (error) {
        result.innerHTML = `<div class="alert alert-danger mb-0">${escapeInvoiceHtml(error.message)}</div>`;
    } finally {
        button.disabled = false;
    }
}

async function loadSpaUsages() {
    const tbody = document.getElementById("spaUsageTableBody");
    if (!tbody) return;
    try {
        const response = await apiRequest("/spa/usages");
        const rows = extractList(response);
        tbody.innerHTML = rows.length ? rows.map((row) => `
            <tr>
                <td>${escapeInvoiceHtml(row.mathucung || "")} - ${escapeInvoiceHtml(row.ten_thucung || "")}</td>
                <td>${escapeInvoiceHtml(row.ten_dichvu)}</td>
                <td>${Number(row.soluong)}</td>
                <td>${formatCurrency(row.thanhtien)}</td>
                <td>${row.invoice_id ? '<span class="badge badge-success">Đã lên hóa đơn</span>' : '<span class="badge badge-warning">Chờ thanh toán</span>'}</td>
                <td>${escapeInvoiceHtml(row.ten_nhanvien || "-")}</td>
                <td>${escapeInvoiceHtml(row.created_at || "-")}</td>
            </tr>
        `).join("") : '<tr><td colspan="7" class="text-center text-muted">Chưa có dịch vụ Spa được ghi nhận</td></tr>';
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-danger text-center">${escapeInvoiceHtml(error.message)}</td></tr>`;
    }
}

// Tải danh sách hóa đơn từ GET /payment/invoices
async function loadInvoices() {
    const tbody = document.getElementById("invoicesTableBody");

    try {
        const response = await apiRequest(invoiceListEndpoint);
        renderInvoices(extractList(response));
    } catch (err) {
        tbody.innerHTML = `
            <tr><td colspan="7" class="text-danger text-center">
                ${escapeInvoiceHtml(err.message || "Lỗi tải danh sách hóa đơn")}
            </td></tr>
        `;
    }
}

function renderInvoices(invoices) {
    const tbody = document.getElementById("invoicesTableBody");
    if (!invoices.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Chưa có hóa đơn thanh toán</td></tr>';
        return;
    }

    const methodMap = {
        TIEN_MAT: "Tiền mặt",
        CHUYEN_KHOAN: "Chuyển khoản",
        THE: "Thẻ",
    };

    const statusMap = {
        DA_THANH_TOAN: '<span class="badge badge-success">Đã thanh toán</span>',
        CHO_THANH_TOAN: '<span class="badge badge-warning">Chờ thanh toán</span>',
        DA_HUY: '<span class="badge badge-danger">Đã hủy</span>',
    };

    tbody.innerHTML = invoices.map((invoice) => {
        const petName = invoice.ten_thucung || "";
        const petLabel = invoice.mathucung
            ? `${escapeInvoiceHtml(invoice.mathucung)} - ${escapeInvoiceHtml(petName)}`
            : escapeInvoiceHtml(petName || `#${invoice.pet_id || ""}`);
        const ownerLabel = escapeInvoiceHtml(invoice.ten_chu_nuoi || `#${invoice.owner_id || ""}`);
        const method = methodMap[invoice.phuongthucthanhtoan] || escapeInvoiceHtml(invoice.phuongthucthanhtoan || "-");
        const status = statusMap[invoice.trangthai] || `<span class="badge badge-secondary">${escapeInvoiceHtml(invoice.trangthai || "-")}</span>`;

        return `
            <tr>
                <td class="font-weight-bold text-primary">${escapeInvoiceHtml(invoice.mahoadon || "-")}</td>
                <td>${petLabel}</td>
                <td>${ownerLabel}</td>
                <td class="font-weight-bold text-success">${formatCurrency(invoice.tongtien)}</td>
                <td>${method}</td>
                <td>${status}</td>
                <td><small>${escapeInvoiceHtml(invoice.ngaytao || "-")}</small></td>
                <td><button type="button" class="btn btn-sm btn-outline-primary" onclick="printInvoice(${Number(invoice.invoice_id)})"><i class="fas fa-print mr-1"></i>In phiếu</button></td>
            </tr>
        `;
    }).join("");
}
