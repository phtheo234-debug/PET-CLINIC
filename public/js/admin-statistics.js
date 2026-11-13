document.addEventListener("DOMContentLoaded", async () => {
    initPage();

    const inputTuNgay = document.getElementById("tu_ngay");
    const inputDenNgay = document.getElementById("den_ngay");
    const errorBox = document.getElementById("thongKeError");
    let revenueChart = null;

    const today = new Date();
    const toDateInput = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    };

    const setThisMonth = () => {
        inputTuNgay.value = toDateInput(new Date(today.getFullYear(), today.getMonth(), 1));
        inputDenNgay.value = toDateInput(today);
    };

    const formatDate = (value) => {
        if (!value) return "";
        const parts = value.substring(0, 10).split("-");
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : value;
    };

    const categoryNames = {
        THUOC: "Thuốc",
        VAC_XIN: "Vắc-xin",
        THUC_AN: "Thức ăn",
        DO_DUNG: "Đồ dùng",
        PHI_KHAM: "Phí khám",
        LUU_TRU: "Lưu trú",
        DICH_VU_SPA: "Dịch vụ Spa"
    };

    const renderRows = (elementId, rows, emptyMessage, renderer, colSpan) => {
        const body = document.getElementById(elementId);
        body.replaceChildren();
        if (!rows || rows.length === 0) {
            const tr = document.createElement("tr");
            const td = document.createElement("td");
            td.colSpan = colSpan;
            td.className = "text-center text-muted";
            td.textContent = emptyMessage;
            tr.appendChild(td);
            body.appendChild(tr);
            return;
        }
        rows.forEach((row) => body.appendChild(renderer(row)));
    };

    const cell = (value, className = "") => {
        const td = document.createElement("td");
        td.textContent = value;
        if (className) td.className = className;
        return td;
    };

    const loadStatistics = async () => {
        errorBox.textContent = "";
        if (!inputTuNgay.value || !inputDenNgay.value || inputTuNgay.value > inputDenNgay.value) {
            errorBox.textContent = "Vui lòng chọn khoảng ngày hợp lệ.";
            return;
        }

        try {
            const query = new URLSearchParams({
                tu_ngay: inputTuNgay.value,
                den_ngay: inputDenNgay.value
            });
            const response = await apiRequest(`/analytics/revenue?${query.toString()}`);
            const data = response.data || {};

            document.getElementById("tongDoanhThu").textContent = formatCurrency(data.tong_doanh_thu || 0);
            document.getElementById("soHoaDon").textContent = data.so_hoa_don || 0;
            document.getElementById("doanhThuTrungBinh").textContent = formatCurrency(data.doanh_thu_trung_binh || 0);

            const dailyRows = data.theo_ngay || [];
            document.getElementById("khongCoDoanhThuNgay").classList.toggle("d-none", dailyRows.length > 0);

            if (revenueChart) revenueChart.destroy();
            const canvas = document.getElementById("chartDoanhThu");
            if (dailyRows.length > 0 && typeof Chart !== "undefined") {
                revenueChart = new Chart(canvas, {
                    type: "bar",
                    data: {
                        labels: dailyRows.map(row => formatDate(row.ngay)),
                        datasets: [{
                            label: "Doanh thu",
                            data: dailyRows.map(row => Number(row.tong_doanh_thu || 0)),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true, ticks: { callback: value => Number(value).toLocaleString("vi-VN") + " đ" } } }
                    }
                });
            }

            renderRows("bangTheoLoai", data.theo_loai_chi_phi || [], "Chưa có khoản thu.", (row) => {
                const tr = document.createElement("tr");
                tr.appendChild(cell(categoryNames[row.loai_chiphi] || row.loai_chiphi || "Khác"));
                tr.appendChild(cell(Number(row.so_hoa_don || 0).toLocaleString("vi-VN"), "text-right"));
                tr.appendChild(cell(formatCurrency(row.tong_doanh_thu || 0), "text-right font-weight-bold"));
                return tr;
            }, 3);

            renderRows("bangTheoNgay", dailyRows, "Chưa có doanh thu.", (row) => {
                const tr = document.createElement("tr");
                tr.appendChild(cell(formatDate(row.ngay)));
                tr.appendChild(cell(Number(row.so_hoa_don || 0).toLocaleString("vi-VN"), "text-right"));
                tr.appendChild(cell(formatCurrency(row.tong_doanh_thu || 0), "text-right font-weight-bold"));
                return tr;
            }, 3);
        } catch (error) {
            errorBox.textContent = "Không thể tải thống kê: " + error.message;
        }
    };


    const loadInventoryAlerts = async () => {
        const formatDate = (value) => {
            if (!value) return "-";
            const parts = String(value).substring(0, 10).split("-");
            return parts.length === 3 ? parts.reverse().join("/") : value;
        };
        const categoryNames = {THUOC:"Thuốc", VAC_XIN:"Vắc-xin", THUC_AN:"Thức ăn"};
        try {
            const response = await apiRequest("/analytics/inventory-alerts");
            const data = response.data || {};
            document.getElementById("countSapHetHan").textContent = data.so_sap_het_han || 0;
            document.getElementById("countDaHetHan").textContent = data.so_da_het_han || 0;

            const soonBody = document.getElementById("tableSapHetHan");
            soonBody.replaceChildren();
            (data.sap_het_han || []).forEach(item => {
                const tr = document.createElement("tr");
                [item.ten_sanpham + " (" + (categoryNames[item.danhmuc] || item.danhmuc) + ")",
                 item.solo, Number(item.soluong).toLocaleString("vi-VN"), formatDate(item.ngayhethan)]
                    .forEach(value => tr.appendChild(cell(value)));
                soonBody.appendChild(tr);
            });
            if (!soonBody.children.length) {
                const tr = document.createElement("tr");
                const td = cell("Không có lô hàng sắp hết hạn.");
                td.colSpan = 4; td.className = "text-center text-muted"; tr.appendChild(td); soonBody.appendChild(tr);
            }

            const expiredBody = document.getElementById("tableDaHetHan");
            expiredBody.replaceChildren();
            (data.da_het_han || []).forEach(item => {
                const tr = document.createElement("tr");
                [item.ten_sanpham + " (" + (categoryNames[item.danhmuc] || item.danhmuc) + ")",
                 item.solo, Number(item.soluong).toLocaleString("vi-VN"), formatDate(item.ngayhethan)]
                    .forEach(value => tr.appendChild(cell(value)));
                const action = document.createElement("td");
                const button = document.createElement("button");
                button.type = "button"; button.className = "btn btn-sm btn-outline-danger";
                button.textContent = "Xóa lô";
                button.addEventListener("click", async () => {
                    if (!confirm("Xóa lô đã hết hạn này khỏi danh sách kho?")) return;
                    try {
                        await apiRequest("/admin/inventory/batches/" + Number(item.batch_id), {method:"DELETE"});
                        await loadInventoryAlerts();
                    } catch (error) { alert("Không thể xóa lô: " + error.message); }
                });
                action.appendChild(button); tr.appendChild(action); expiredBody.appendChild(tr);
            });
            if (!expiredBody.children.length) {
                const tr = document.createElement("tr");
                const td = cell("Không có lô hàng đã hết hạn.");
                td.colSpan = 5; td.className = "text-center text-muted"; tr.appendChild(td); expiredBody.appendChild(tr);
            }
        } catch (error) {
            document.getElementById("tableSapHetHan").innerHTML = '<tr><td colspan="4" class="text-danger text-center">Không tải được cảnh báo hạn sử dụng.</td></tr>';
            document.getElementById("tableDaHetHan").innerHTML = '<tr><td colspan="5" class="text-danger text-center">Không tải được cảnh báo hạn sử dụng.</td></tr>';
        }
    };

    setThisMonth();
    document.getElementById("btnThongKe").addEventListener("click", loadStatistics);
    document.getElementById("btnThangNay").addEventListener("click", () => {
        setThisMonth();
        loadStatistics();
    });
    await Promise.all([loadStatistics(), loadInventoryAlerts()]);
});
