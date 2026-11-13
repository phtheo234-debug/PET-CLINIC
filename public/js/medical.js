async function loadMedicalInventory() {
    const select = document.getElementById("medicalInventorySelect");
    try {
        const response = await apiRequest("/inventory");
        medicalInventoryItems = extractList(response).filter((item) => ["THUOC", "VAC_XIN"].includes(item.danhmuc) && Number(item.tong_ton_kho) > 0);
        select.innerHTML = '<option value="">-- Chọn thuốc hoặc vắc-xin --</option>' + medicalInventoryItems.map((item) =>
            '<option value="' + Number(item.item_id) + '">' + (item.danhmuc === "THUOC" ? "Thuốc" : "Vắc-xin") + " · " + escapeMedicalHtml(item.ten_sanpham) + " (còn " + Number(item.tong_ton_kho) + " " + escapeMedicalHtml(item.donvitinh) + ")</option>"
        ).join("");
        if (!medicalInventoryItems.length) select.innerHTML = '<option value="">Kho chưa có thuốc/vắc-xin còn hàng</option>';
    } catch (error) { select.innerHTML = '<option value="">Không tải được danh mục kho</option>'; console.error(error); }
}

function escapeMedicalHtml(value) {
    const map = {"&":"&amp;", "<":"&lt;", ">":"&gt;", '"' : "&quot;", "'": "&#039;"};
    return String(value ?? "").replace(/[&<>"']/g, (char) => map[char]);
}

function addMedicalInventoryItem() {
    const itemId = Number(document.getElementById("medicalInventorySelect").value);
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
        const labels = {DANG_DIEU_TRI:"Đang điều trị",HOAN_THANH:"Hoàn thành",CHUYEN_VIEN:"Chuyển viện"};
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
        OUTPATIENT: "HOAN_THANH",
        INPATIENT: "DANG_DIEU_TRI",
        SURGERY: "DANG_DIEU_TRI",
        TRANSFER: "CHUYEN_VIEN",
    };
    return map[huongXuLy] || "DANG_DIEU_TRI";
}

document.addEventListener("DOMContentLoaded", async () => {
    initPage();
    await Promise.all([loadPetsDropdown(), loadMedicalRecords(), loadMedicalInventory()]);
    document.getElementById("btnAddMedicalItem")?.addEventListener("click", addMedicalInventoryItem);
    document.getElementById("medicalItemsBody")?.addEventListener("click", (event) => {
        const button = event.target.closest("[data-remove-medical-item]");
        if (!button) return;
        selectedMedicalItems.splice(Number(button.dataset.removeMedicalItem), 1);
        renderSelectedMedicalItems();
    });

    const actionSelect = document.getElementById("huong_xuly");
    actionSelect?.addEventListener("change", updateIsolationOption);
    updateIsolationOption();

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

            const btn = document.getElementById("btnSaveMedical");
            btn.disabled = true;
            btn.innerHTML =
                '<i class="fas fa-spinner fa-spin mr-1"></i> Đang lưu...';

            const huongXuLy = document.getElementById("huong_xuly").value;

            const payload = {
                pet_id: petId,
                cannangkhi_kham: weight,
                trieuchung: document.getElementById("trieuchung").value.trim(),
                chandoan: document.getElementById("chandoan").value.trim(),
                items: selectedMedicalItems,
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
                e.target.reset();
                document.getElementById("doseSuggestion").innerText =
                    "Nhập cân nặng để tự động tính liều mg/kg";
                await loadMedicalRecords();
                const savedRecord = res?.data?.data || res?.data || {};
                selectedMedicalItems = [];
                renderSelectedMedicalItems();
                alert((res?.message || "Đã lưu phiếu khám bệnh thành công!") + " Bạn có thể nhấn In phiếu trong danh sách lịch sử khám.");
            } catch (err) {
                alert("Lỗi lưu phiếu khám: " + err.message);
            } finally {
                btn.disabled = false;
                btn.innerHTML =
                    '<i class="fas fa-save mr-1"></i> Lưu Phiếu Khám';
            }
        });
});

function updateIsolationOption() {
    const action = document.getElementById("huong_xuly")?.value;
    const wrapper = document.getElementById("isolationOption");
    const checkbox = document.getElementById("yeu_cau_cach_ly");
    const canRequestStay = ["INPATIENT", "SURGERY"].includes(action);

    wrapper?.classList.toggle("d-none", !canRequestStay);

    if (!canRequestStay && checkbox) {
        checkbox.checked = false;
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
        HOAN_THANH: '<span class="badge badge-success">Hoàn thành</span>',
        DANG_DIEU_TRI: '<span class="badge badge-warning">Đang điều trị</span>',
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
