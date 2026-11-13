async function loadMedicalInventory() {
    const select = document.getElementById("medicalInventorySelect");
    const search = document.getElementById("medicalInventorySearch");
    const suggestions = document.getElementById("medicalInventorySuggestions");
    try {
        const response = await apiRequest("/inventory");
        medicalInventoryItems = extractList(response).filter((item) =>
            ["THUOC", "VAC_XIN"].includes(item.danhmuc) && Number(item.tong_ton_kho) > 0
        );
        select.innerHTML = '<option value="">-- Chọn thuốc hoặc vắc-xin --</option>' + medicalInventoryItems.map((item) =>
            '<option value="' + Number(item.item_id) + '">' + escapeMedicalHtml(item.ten_sanpham) + '</option>'
        ).join("");
        suggestions.innerHTML = medicalInventoryItems.map((item) => {
            const code = item.masanpham ? item.masanpham + " - " : "";
            const kind = item.danhmuc === "THUOC" ? "Thuốc" : "Vắc-xin";
            return '<option value="' + escapeMedicalHtml(code + item.ten_sanpham) + '">' +
                escapeMedicalHtml(kind + " · còn " + Number(item.tong_ton_kho) + " " + (item.donvitinh || "")) + '</option>';
        }).join("");
        if (!medicalInventoryItems.length) {
            suggestions.innerHTML = '<option value="Kho chưa có thuốc/vắc-xin còn hàng"></option>';
        }
        search.value = "";
        select.value = "";
    } catch (error) {
        suggestions.innerHTML = '<option value="Không tải được danh mục kho"></option>';
        console.error(error);
    }
}

function syncMedicalInventorySearch() {
    const search = document.getElementById("medicalInventorySearch");
    const select = document.getElementById("medicalInventorySelect");
    const value = search.value.trim().toLocaleLowerCase("vi");
    const item = medicalInventoryItems.find((entry) => {
        const codeName = ((entry.masanpham || "") + " - " + (entry.ten_sanpham || "")).toLocaleLowerCase("vi");
        return value && (value === codeName || value === String(entry.ten_sanpham || "").toLocaleLowerCase("vi"));
    });
    select.value = item ? String(item.item_id) : "";
    return item || null;
}

function escapeMedicalHtml(value) {
    const map = {"&":"&amp;", "<":"&lt;", ">":"&gt;", '"' : "&quot;", "'": "&#039;"};
    return String(value ?? "").replace(/[&<>"']/g, (char) => map[char]);
}

function addMedicalInventoryItem() {
    const matchedItem = syncMedicalInventorySearch();
    const itemId = Number(matchedItem?.item_id || document.getElementById("medicalInventorySelect").value);
    const quantity = Number(document.getElementById("medicalInventoryQty").value);
    const note = document.getElementById("medicalInventoryNote").value.trim();
    const item = medicalInventoryItems.find((entry) => Number(entry.item_id) === itemId);
    if (!item || !Number.isInteger(quantity) || quantity < 1) { alert("Vui lòng chọn mặt hàng trong kho và nhập số lượng hợp lệ."); return; }
    const alreadySelected = selectedMedicalItems.filter((entry) => entry.item_id === itemId).reduce((sum, entry) => sum + entry.soluong, 0);
    if (alreadySelected + quantity > Number(item.tong_ton_kho)) { alert("Số lượng chọn vượt quá tồn kho hiện tại."); return; }
    selectedMedicalItems.push({item_id:itemId, soluong:quantity, huongdan:note});
    renderSelectedMedicalItems();
    document.getElementById("medicalInventoryQty").value = 1;
    document.getElementById("medicalInventoryNote").value = "";
}

function renderSelectedMedicalItems() {
    const tbody = document.getElementById("medicalItemsBody");
    if (!selectedMedicalItems.length) { tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Chưa chọn thuốc/vắc-xin</td></tr>'; return; }
    tbody.innerHTML = selectedMedicalItems.map((entry, index) => {
        const item = medicalInventoryItems.find((row) => Number(row.item_id) === entry.item_id);
        return '<tr><td>' + (item?.danhmuc === "VAC_XIN" ? "Vắc-xin" : "Thuốc") + '</td><td>' + escapeMedicalHtml(item?.ten_sanpham || "") + '</td><td>' + entry.soluong + " " + escapeMedicalHtml(item?.donvitinh || "") + '</td><td>' + escapeMedicalHtml(entry.huongdan || "-") + '</td><td><button type="button" class="btn btn-sm btn-outline-danger" data-remove-medical-item="' + index + '"><i class="fas fa-trash"></i></button></td></tr>';
    }).join("");
}

async function printMedicalRecord(recordId) {
    try {
        const response = await apiRequest("/medical/records/" + recordId);
        const record = response?.data?.data || response?.data || {};
        const labels = {CHUA_KHAM:"Chưa khám",DANG_DIEU_TRI:"Đang điều trị",HOAN_THANH:"Hoàn thành",CHUYEN_VIEN:"Chuyển viện"};
        const rows = (record.items || []).map((item, index) => "<tr><td>" + (index+1) + "</td><td>" + escapeMedicalHtml(item.ten_sanpham) + "</td><td>" + (item.danhmuc === "VAC_XIN" ? "Vắc-xin" : "Thuốc") + "</td><td>" + Number(item.soluong) + " " + escapeMedicalHtml(item.donvitinh) + "</td><td>" + escapeMedicalHtml(item.huongdan || "-") + "</td></tr>").join("");
        const win = window.open("", "_blank", "width=900,height=750");
        if (!win) { alert("Trình duyệt đang chặn cửa sổ in. Hãy cho phép popup rồi thử lại."); return; }
        win.document.write("<!DOCTYPE html><html lang=\"vi\"><head><meta charset=\"UTF-8\"><title>Phiếu khám</title><style>body{font-family:Arial;padding:28px;color:#222}h2,h3{text-align:center}table{width:100%;border-collapse:collapse;margin-top:16px}th,td{border:1px solid #777;padding:8px;text-align:left}.sign{display:flex;justify-content:space-between;margin-top:55px}.sign div{width:40%;text-align:center}</style></head><body>" +
            "<h2>PET CLINIC</h2><h3>PHIẾU KẾT QUẢ KHÁM BỆNH</h3>" +
            "<p><b>Mã phiếu:</b> " + record.record_id + " &nbsp; <b>Ngày khám:</b> " + escapeMedicalHtml(record.ngaykham || "") + "</p>" +
            "<p><b>Thú cưng:</b> " + escapeMedicalHtml(record.ten_thucung || "") + " (" + escapeMedicalHtml(record.mathucung || "") + ")</p>" +
            "<p><b>Loài / Giống:</b> " + escapeMedicalHtml(record.loai || "") + " / " + escapeMedicalHtml(record.giong || "") + "</p>" +
            "<p><b>Chủ nuôi:</b> " + escapeMedicalHtml(record.ten_chu_nuoi || "") + " &nbsp; <b>Điện thoại:</b> " + escapeMedicalHtml(record.sdt_chu_nuoi || "") + "</p>" +
            "<p><b>Cân nặng khi khám:</b> " + escapeMedicalHtml(record.cannangkhi_kham) + " kg</p>" +
            "<p><b>Triệu chứng:</b><br>" + escapeMedicalHtml(record.trieuchung || "-") + "</p><p><b>Chẩn đoán:</b><br>" + escapeMedicalHtml(record.chandoan || "-") + "</p>" +
            "<p><b>Trạng thái:</b> " + escapeMedicalHtml(labels[record.trangthai] || record.trangthai || "-") + "</p>" +
            (record.surgery_id ? "<p><b>Phẫu thuật:</b> " + escapeMedicalHtml(record.ma_phauthuat || "") + " - " + escapeMedicalHtml(record.ten_phauthuat || "") + " · " + Number(record.surgery_price || 0).toLocaleString("vi-VN") + " đ</p>" : "") +
            "<h3>THUỐC / VẮC-XIN CHỈ ĐỊNH</h3><table><thead><tr><th>STT</th><th>Tên</th><th>Loại</th><th>Số lượng</th><th>Hướng dẫn</th></tr></thead><tbody>" + (rows || "<tr><td colspan=\"5\">Không kê thuốc/vắc-xin</td></tr>") + "</tbody></table>" +
            "<div class=\"sign\"><div>Bác sĩ khám<br><br><br>" + escapeMedicalHtml(record.ten_bac_si || "") + "</div><div>Chủ nuôi xác nhận<br><br><br>........................</div></div><script>window.onload=function(){window.print();};<\\/script></body></html>");
        win.document.close();
    } catch (error) { alert("Không thể tải phiếu khám để in: " + error.message); }
}
let medicalEndpoint = "/medical/records";
let medicalInventoryItems = [];
let selectedMedicalItems = [];

function extractList(res) {
    if (Array.isArray(res)) return res;
    if (res && Array.isArray(res.data)) return res.data;
    if (res && res.data && Array.isArray(res.data.data)) return res.data.data;
    return [];
}

// Ánh xạ Hướng xử lý trên giao diện sang đúng ENUM trangthai của Backend
function mapActionToStatus(huongXuLy) {
    const map = {
        OUTPATIENT: "CHUA_KHAM",
        INPATIENT: "CHUA_KHAM",
        SURGERY: "CHUA_KHAM",
        TRANSFER: "CHUYEN_VIEN",
    };
    return map[huongXuLy] || "DANG_DIEU_TRI";
}

document.addEventListener("DOMContentLoaded", async () => {
    initPage();
    await Promise.all([loadPetsDropdown(), loadMedicalRecords(), loadMedicalInventory()]);
    if (document.getElementById("medicalQueueBody")) {
        document.getElementById("btnSelectPriorityQueue")?.addEventListener("click", async event => {
            const button = event.currentTarget;
            const queueId = Number(button.dataset.queueId || 0);
            if (!queueId) return;
            button.disabled = true;
            try {
                const response = await apiRequest("/medical/queue/" + queueId + "/start", {method:"POST"});
                const data = response?.data || {};
                const petId = Number(data.pet_id || data.data?.pet_id || 0);
                const petSelect = document.getElementById("pet_select");
                if (!petId || !petSelect || !Array.from(petSelect.options).some(option => Number(option.value) === petId)) {
                    throw new Error("Không tìm thấy thú cưng tương ứng trong ô Thú cưng thăm khám.");
                }
                petSelect.value = String(petId);
                petSelect.dispatchEvent(new Event("change", {bubbles:true}));
                await loadMedicalQueue();
                document.getElementById("medicalForm")?.scrollIntoView({behavior:"smooth", block:"start"});
            } catch (error) {
                alert("Không thể chọn ca khám: " + error.message);
                await loadMedicalQueue();
            }
        });
        await loadMedicalQueue();
    }
    document.getElementById("btnAddMedicalItem")?.addEventListener("click", addMedicalInventoryItem);
    document.getElementById("medicalInventorySearch")?.addEventListener("input", syncMedicalInventorySearch);
    document.getElementById("medicalInventorySearch")?.addEventListener("change", syncMedicalInventorySearch);
    let lastSavedMedicalRecordId = null;
    const saveButtonDefaultHtml = '<i class="fas fa-save mr-1"></i> Lưu Phiếu Khám';
    const printCurrentButton = document.getElementById("btnPrintCurrentMedical");
    const markMedicalFormDirty = () => {
        lastSavedMedicalRecordId = null;
        if (printCurrentButton) printCurrentButton.disabled = true;
        const saveButton = document.getElementById("btnSaveMedical");
        if (saveButton && !saveButton.disabled) saveButton.innerHTML = saveButtonDefaultHtml;
    };
    document.getElementById("medicalForm")?.addEventListener("input", markMedicalFormDirty);
    document.getElementById("medicalForm")?.addEventListener("change", markMedicalFormDirty);
    document.getElementById("btnPrintCurrentMedical")?.addEventListener("click", () => { if (lastSavedMedicalRecordId) printMedicalRecord(lastSavedMedicalRecordId); });
    document.getElementById("medicalItemsBody")?.addEventListener("click", (event) => {
        const button = event.target.closest("[data-remove-medical-item]");
        if (!button) return;
        selectedMedicalItems.splice(Number(button.dataset.removeMedicalItem), 1);
        renderSelectedMedicalItems();
    });

    const actionSelect = document.getElementById("huong_xuly");
    actionSelect?.addEventListener("change", updateMedicalSections);
    document.getElementById("yeu_cau_cach_ly")?.addEventListener("change", updateMedicalSections);
    loadSurgeryTypes();
    if (document.getElementById("surgeryCatalogBody")) {
        document.getElementById("surgeryForm")?.addEventListener("submit", saveSurgery);
        loadSurgeryCatalog();
    }
    updateMedicalSections();

    document
        .getElementById("cannangkhi_kham")
        .addEventListener("input", (e) => {
            const weight = parseFloat(e.target.value) || 0;
            const doseHint = document.getElementById("doseSuggestion");
            doseHint.innerText =
                weight > 0
                    ? `Gợi ý liều chuẩn (10mg/kg): ${(weight * 10).toFixed(1)} mg/lần`
                    : "Nhập cân nặng để tự động tính liều mg/kg";
        });

    document
        .getElementById("medicalForm")
        .addEventListener("submit", async (e) => {
            e.preventDefault();

            const petId = Number(document.getElementById("pet_select").value);
            const weight = parseFloat(
                document.getElementById("cannangkhi_kham").value,
            );

            if (!petId) {
                alert("Vui lòng chọn thú cưng thăm khám!");
                return;
            }
            if (isNaN(weight) || weight <= 0) {
                alert("Cân nặng lúc khám phải lớn hơn 0 kg!");
                document.getElementById("cannangkhi_kham").focus();
                return;
            }
            const huongXuLy = document.getElementById("huong_xuly").value;
            if (huongXuLy === "SURGERY" && !document.getElementById("medicalSurgerySelect")?.value) {
                alert("Vui lòng chọn loại phẫu thuật.");
                document.getElementById("medicalSurgerySelect")?.focus();
                return;
            }

            const btn = document.getElementById("btnSaveMedical");
            btn.disabled = true;
            btn.innerHTML =
                '<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...';

            const payload = {
                pet_id: petId,
                cannangkhi_kham: weight,
                trieuchung: document.getElementById("trieuchung").value.trim(),
                chandoan: document.getElementById("chandoan").value.trim(),
                items: huongXuLy === "TRANSFER" ? [] : selectedMedicalItems,
                surgery_id: huongXuLy === "SURGERY" ? Number(document.getElementById("medicalSurgerySelect").value) : null,
                huong_xuly: huongXuLy,
                yeu_cau_cach_ly: document.getElementById("yeu_cau_cach_ly")
                    ?.checked
                    ? 1
                    : 0,
                trangthai: mapActionToStatus(huongXuLy),
            };

            try {
                const res = await apiRequest(medicalEndpoint, {
                    method: "POST",
                    body: JSON.stringify(payload),
                });
                const savedRecord = res?.data?.data || res?.data || {};
                lastSavedMedicalRecordId = Number(savedRecord.record_id || savedRecord.id || 0) || null;
                updateMedicalSections();
                document.getElementById("doseSuggestion").innerText =
                    "Nhập cân nặng để tự động tính liều mg/kg";
                await loadMedicalRecords();
                await loadMedicalQueue();
                const printButton = document.getElementById("btnPrintCurrentMedical");
                if (printButton) printButton.disabled = !lastSavedMedicalRecordId;
                if (lastSavedMedicalRecordId) {
                    btn.innerHTML = '<i class="fas fa-check-circle mr-1"></i> Đã lưu';
                }
                selectedMedicalItems = [];
                renderSelectedMedicalItems();
                alert((res?.message || "Đã lưu phiếu khám bệnh thành công!") + (lastSavedMedicalRecordId ? " Bạn có thể nhấn nút In phiếu khám ngay cạnh nút lưu." : " Bạn có thể in trong danh sách lịch sử khám."));
            } catch (err) {
                alert("Lỗi lưu phiếu khám: " + err.message);
            } finally {
                btn.disabled = false;
                if (!lastSavedMedicalRecordId) btn.innerHTML = saveButtonDefaultHtml;
            }
        });
});

async function loadSurgeryTypes() {
    const select = document.getElementById("medicalSurgerySelect");
    if (!select) return;
    try {
        const response = await apiRequest("/surgeries");
        const surgeries = extractList(response);
        select.innerHTML = '<option value="">-- Chọn loại phẫu thuật --</option>' + surgeries
            .filter(item => Number(item.trangthai ?? 1) === 1)
            .map(item => '<option value="' + Number(item.surgery_id) + '">' +
                escapeMedicalHtml(item.ma_phauthuat) + ' · ' + escapeMedicalHtml(item.ten_phauthuat) +
                ' (' + Number(item.gia).toLocaleString("vi-VN") + ' đ)</option>').join("");
        if (!surgeries.length) select.innerHTML = '<option value="">Chưa có loại phẫu thuật trong danh mục</option>';
    } catch (error) {
        select.innerHTML = '<option value="">Không tải được danh mục phẫu thuật</option>';
        console.error(error);
    }
}

function updateMedicalSections() {
    const action = document.getElementById("huong_xuly")?.value;
    const checkbox = document.getElementById("yeu_cau_cach_ly");
    const canRequestStay = ["OUTPATIENT", "INPATIENT", "SURGERY"].includes(action);
    const showMedicine = ["OUTPATIENT", "INPATIENT", "SURGERY"].includes(action);
    document.getElementById("isolationOption")?.classList.toggle("d-none", !canRequestStay);
    document.getElementById("medicalInventorySection")?.classList.toggle("d-none", !showMedicine);
    document.getElementById("medicalSurgerySection")?.classList.toggle("d-none", action !== "SURGERY");
    if (!canRequestStay && checkbox) checkbox.checked = false;
    if (action === "TRANSFER") {
        selectedMedicalItems = [];
        renderSelectedMedicalItems();
    }
}

async function loadPetsDropdown() {
    const select = document.getElementById("pet_select");
    const weightInput = document.getElementById("cannangkhi_kham");

    try {
        const res = await apiRequest("/pets");
        const pets = extractList(res);

        if (!pets.length) {
            select.innerHTML =
                '<option value="">-- Chưa có thú cưng trong CSDL --</option>';
            return;
        }

        select.innerHTML =
            '<option value="">-- Chọn thú cưng --</option>' +
            pets
                .map((p) => {
                    const id = p.id ?? p.pet_id;
                    const code = p.mathucung || `PET-${id}`;
                    const species = p.species || p.loai || "";
                    const name = p.name || p.ten_thucung || "";
                    const weight = p.weight ?? p.can_nang ?? "";

                    return `
                    <option value="${id}" data-weight="${weight}">
                        ${code} (${species}${species && name ? " - " : ""}${name})
                    </option>
                `;
                })
                .join("");

        // Khi chọn thú cưng, tự động điền cân nặng hiện tại
        select.addEventListener("change", function () {
            const selectedOption = this.options[this.selectedIndex];
            const currentWeight = selectedOption?.dataset.weight || "";

            weightInput.value = currentWeight;

            weightInput.dispatchEvent(new Event("input"));
        });
    } catch (err) {
        select.innerHTML = `<option value="">Lỗi tải danh sách: ${err.message}</option>`;
    }
}

async function loadMedicalRecords() {
    const candidates = ["/medical/records", "/medical", "/medical-records"];
    let lastError = null;

    for (const ep of candidates) {
        try {
            const res = await apiRequest(ep);
            medicalEndpoint = ep;
            renderMedicalHistory(extractList(res));
            return;
        } catch (err) {
            lastError = err;
            if (!err.message.includes("could not be found")) {
                break;
            }
        }
    }

    document.getElementById("medicalHistoryBody").innerHTML =
        `<tr><td colspan="7" class="text-danger text-center">${lastError ? lastError.message : "Không tải được dữ liệu"}</td></tr>`;
}

function renderMedicalHistory(records) {
    const statusMap = {
        CHUA_KHAM: '<span class="badge badge-secondary">Chưa khám</span>',
        DANG_DIEU_TRI: '<span class="badge badge-warning">Đang điều trị</span>',
        HOAN_THANH: '<span class="badge badge-success">Hoàn thành</span>',
        CHUYEN_VIEN: '<span class="badge badge-danger">Chuyển viện</span>',
    };
    const actionLabels = {
        OUTPATIENT: "Ngoại trú",
        INPATIENT: "Chỉ định nội trú",
        SURGERY: "Chỉ định phẫu thuật",
        TRANSFER: "Chuyển viện",
    };
    const boardingLabels = {
        pending:
            '<span class="badge badge-warning">Chờ nhân viên xác nhận lồng</span>',
        checked_in:
            '<span class="badge badge-success">Đã xếp lồng / Đang nội trú</span>',
        completed:
            '<span class="badge badge-secondary">Đã kết thúc lưu trú</span>',
        cancelled:
            '<span class="badge badge-secondary">Đã hủy yêu cầu lưu trú</span>',
    };

    const tbody = document.getElementById("medicalHistoryBody");
    if (!records.length) {
        tbody.innerHTML =
            '<tr><td colspan="7" class="text-center text-muted">Chưa có lịch sử khám bệnh</td></tr>';
        return;
    }

    tbody.innerHTML = records
        .map((r) => {
            const petLabel = r.pet
                ? `${r.pet.mathucung || ""} (${r.pet.name || r.pet.ten_thucung || ""})`
                : r.mathucung
                  ? `${r.mathucung}${r.ten_thucung ? " (" + r.ten_thucung + ")" : ""}`
                  : `#${r.pet_id}`;
            const statusKey = r.trangthai;
            const actionLabel =
                actionLabels[r.huong_xuly] ||
                statusMap[statusKey] ||
                statusKey ||
                "-";
            const boardingLabel = boardingLabels[r.boarding_status]
                ? `<br>${boardingLabels[r.boarding_status]}`
                : "";
            const cageBadge = r.malong
                ? `<br><small class="text-info font-weight-bold"><i class="fas fa-home"></i> Lồng: ${r.malong}</small>`
                : "";

            return `
            <tr>
                <td><strong>${petLabel}</strong></td>
                <td>${r.cannangkhi_kham} kg</td>
                <td>${r.trieuchung || ""}</td>
                <td><strong>${r.chandoan || ""}</strong></td>
                <td>${actionLabel}${boardingLabel}${cageBadge}</td>
                <td><small>${r.ngaykham || r.created_at || ""}</small></td>
                <td><button type="button" class="btn btn-sm btn-outline-primary" onclick="printMedicalRecord(${Number(r.record_id)})"><i class="fas fa-print mr-1"></i>In phiếu</button></td>
            </tr>
        `;
        })
        .join("");
}


let surgeryCatalog = [];

async function loadSurgeryCatalog() {
    const tbody = document.getElementById("surgeryCatalogBody");
    if (!tbody) return;
    try {
        const response = await apiRequest("/surgeries");
        surgeryCatalog = extractList(response);
        tbody.innerHTML = surgeryCatalog.length ? surgeryCatalog.map(item => `
            <tr>
                <td>${escapeMedicalHtml(item.ma_phauthuat)}</td>
                <td>${escapeMedicalHtml(item.ten_phauthuat)}</td>
                <td>${Number(item.gia).toLocaleString("vi-VN")} đ</td>
                <td>${Number(item.trangthai) ? '<span class="badge badge-success">Đang áp dụng</span>' : '<span class="badge badge-secondary">Ngừng áp dụng</span>'}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editSurgery(${Number(item.surgery_id)})">Sửa</button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteSurgery(${Number(item.surgery_id)})">Xóa</button>
                </td>
            </tr>`).join("") : '<tr><td colspan="5" class="text-center text-muted">Chưa có loại phẫu thuật.</td></tr>';
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-danger text-center">' + escapeMedicalHtml(error.message) + '</td></tr>';
    }
}

function openSurgeryForm(item = null) {
    const form = document.getElementById("surgeryForm");
    if (!form) return;
    form.reset();
    document.getElementById("surgery_form_id").value = item ? item.surgery_id : "";
    document.getElementById("surgeryFormTitle").innerText = item ? "Cập nhật loại phẫu thuật" : "Thêm loại phẫu thuật";
    document.getElementById("surgery_code").value = item ? item.ma_phauthuat : "";
    document.getElementById("surgery_name").value = item ? item.ten_phauthuat : "";
    document.getElementById("surgery_price").value = item ? item.gia : "";
    document.getElementById("surgery_status").value = item ? String(Number(item.trangthai)) : "1";
    $("#surgeryFormModal").modal("show");
}

function editSurgery(id) {
    const item = surgeryCatalog.find(row => Number(row.surgery_id) === Number(id));
    if (item) openSurgeryForm(item);
}

async function saveSurgery(event) {
    event.preventDefault();
    const id = document.getElementById("surgery_form_id").value;
    const payload = {
        ma_phauthuat: document.getElementById("surgery_code").value.trim(),
        ten_phauthuat: document.getElementById("surgery_name").value.trim(),
        gia: Number(document.getElementById("surgery_price").value),
        trangthai: Number(document.getElementById("surgery_status").value)
    };
    const button = document.getElementById("btnSaveSurgery");
    button.disabled = true;
    try {
        await apiRequest(id ? "/admin/surgeries/" + id : "/admin/surgeries", {
            method: id ? "PUT" : "POST",
            body: JSON.stringify(payload)
        });
        $("#surgeryFormModal").modal("hide");
        await Promise.all([loadSurgeryCatalog(), loadSurgeryTypes()]);
        alert(id ? "Đã cập nhật loại phẫu thuật." : "Đã thêm loại phẫu thuật.");
    } catch (error) {
        alert("Không thể lưu loại phẫu thuật: " + error.message);
    } finally {
        button.disabled = false;
    }
}

async function deleteSurgery(id) {
    const item = surgeryCatalog.find(row => Number(row.surgery_id) === Number(id));
    if (!item || !confirm("Xóa/ngừng áp dụng loại phẫu thuật " + item.ten_phauthuat + "?")) return;
    try {
        const response = await apiRequest("/admin/surgeries/" + id, { method: "DELETE" });
        await Promise.all([loadSurgeryCatalog(), loadSurgeryTypes()]);
        alert(response?.message || "Đã xử lý loại phẫu thuật.");
    } catch (error) {
        alert("Không thể xóa loại phẫu thuật: " + error.message);
    }
}


async function loadMedicalQueue() {
    const body = document.getElementById("medicalQueueBody");
    const featured = document.getElementById("medicalQueueFeatured");
    const selectButton = document.getElementById("btnSelectPriorityQueue");
    if (!body) return;
    try {
        const response = await apiRequest("/medical/queue");
        const rows = extractList(response);
        const active = rows.find(item => item.queue_status === "DANG_KHAM");
        const next = active || rows.find(item => item.queue_status === "CHO_KHAM");
        if (active) {
            const petSelect = document.getElementById("pet_select");
            if (petSelect && Array.from(petSelect.options).some(option => Number(option.value) === Number(active.pet_id))) {
                petSelect.value = String(active.pet_id);
                petSelect.dispatchEvent(new Event("change", {bubbles:true}));
            }
        }
        if (featured) {
            featured.innerHTML = next
                ? '<div class="d-flex flex-wrap justify-content-between align-items-center"><div><div class="small text-uppercase font-weight-bold text-danger mb-1">' + (active ? 'CA ĐANG ĐƯỢC KHÁM' : 'THÚ CƯNG TIẾP THEO') + '</div><h5 class="mb-1 font-weight-bold">' + escapeMedicalHtml(next.ten_thucung || 'Chưa có tên') + ' <small class="text-muted">(' + escapeMedicalHtml(next.mathucung || '') + ')</small></h5><div>' + escapeMedicalHtml(next.ten_chu_nuoi || 'Chưa có thông tin chủ nuôi') + '</div>' + (next.ghi_chu ? '<div class="mt-2 text-danger font-weight-bold"><i class="fas fa-exclamation-circle mr-1"></i>' + escapeMedicalHtml(next.ghi_chu) + '</div>' : '<div class="small text-muted mt-1">Không có ghi chú ưu tiên</div>') + '</div><span class="badge ' + (active ? 'badge-warning' : 'badge-danger') + ' p-2">' + (active ? 'Đang khám' : 'Ưu tiên tiếp theo') + '</span></div>'
                : '<span class="text-muted">Hiện không có thú cưng chờ khám.</span>';
        }
        if (selectButton) {
            selectButton.dataset.queueId = next && !active && next.queue_status === "CHO_KHAM" ? String(next.queue_id) : "";
            selectButton.disabled = !next || !!active || next.queue_status !== "CHO_KHAM";
            selectButton.innerHTML = active
                ? '<i class="fas fa-stethoscope mr-1"></i> Đang khám'
                : next ? '<i class="fas fa-hand-pointer mr-1"></i> Chọn' : '<i class="fas fa-check mr-1"></i> Không còn ca chờ';
        }
        const statusLabels = {
            CHO_KHAM: '<span class="badge badge-secondary">Chưa khám</span>',
            DANG_KHAM: '<span class="badge badge-warning">Đang khám</span>',
            DA_KHAM: '<span class="badge badge-success">Đã khám</span>'
        };
        body.innerHTML = rows.length ? rows.map(item => {
            const isPriority = item.queue_status === "CHO_KHAM" && !!String(item.ghi_chu || "").trim();
            const rowClass = item.queue_status === "DANG_KHAM" ? "table-warning" : (isPriority ? "table-danger" : "");
            return '<tr class="' + rowClass + '">' +
                '<td><strong>' + escapeMedicalHtml(item.ten_thucung || "-") + '</strong><br><small class="text-muted">' + escapeMedicalHtml(item.mathucung || "") + ' · ' + escapeMedicalHtml(item.loai || "") + ' ' + escapeMedicalHtml(item.giong || "") + '</small></td>' +
                '<td>' + escapeMedicalHtml(item.ten_chu_nuoi || "-") + '<br><small class="text-muted">' + escapeMedicalHtml(item.sdt_chu_nuoi || "") + '</small></td>' +
                '<td>' + (statusLabels[item.queue_status] || '<span class="badge badge-secondary">Chưa khám</span>') + '</td>' +
                '<td>' + (item.ghi_chu ? '<span class="' + (isPriority ? 'text-danger font-weight-bold' : '') + '">' + escapeMedicalHtml(item.ghi_chu) + '</span>' : '<span class="text-muted">—</span>') + '</td></tr>';
        }).join("") : '<tr><td colspan="4" class="text-center text-muted">Chưa có thú cưng trong danh sách chờ khám hôm nay.</td></tr>';
    } catch (error) {
        if (featured) featured.innerHTML = '<span class="text-danger">' + escapeMedicalHtml(error.message || "Không tải được danh sách chờ") + '</span>';
        if (selectButton) { selectButton.disabled = true; selectButton.textContent = "Không tải được danh sách"; }
        body.innerHTML = '<tr><td colspan="4" class="text-danger text-center">' + escapeMedicalHtml(error.message || "Không tải được danh sách chờ") + '</td></tr>';
    }
}
