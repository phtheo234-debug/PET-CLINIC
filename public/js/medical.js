let medicalEndpoint = "/medical/records";

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
    await Promise.all([loadPetsDropdown(), loadMedicalRecords()]);

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
                don_thuoc: document.getElementById("don_thuoc").value.trim(),
                huong_xuly: huongXuLy,
                yeu_cau_cach_ly: document.getElementById("yeu_cau_cach_ly")?.checked ? 1 : 0,
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
                alert(res?.message || "Đã lưu phiếu khám bệnh thành công!");
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
                    return `<option value="${id}">${code} (${species}${species && name ? " - " : ""}${name})</option>`;
                })
                .join("");
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
        `<tr><td colspan="6" class="text-danger text-center">${lastError ? lastError.message : "Không tải được dữ liệu"}</td></tr>`;
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
        pending: '<span class="badge badge-warning">Chờ nhân viên xác nhận lồng</span>',
        checked_in: '<span class="badge badge-success">Đã xếp lồng / Đang nội trú</span>',
        completed: '<span class="badge badge-secondary">Đã kết thúc lưu trú</span>',
        cancelled: '<span class="badge badge-secondary">Đã hủy yêu cầu lưu trú</span>',
    };

    const tbody = document.getElementById("medicalHistoryBody");
    if (!records.length) {
        tbody.innerHTML =
            '<tr><td colspan="6" class="text-center text-muted">Chưa có lịch sử khám bệnh</td></tr>';
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
            const actionLabel = actionLabels[r.huong_xuly] || statusMap[statusKey] || statusKey || "-";
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
            </tr>
        `;
        })
        .join("");
}
