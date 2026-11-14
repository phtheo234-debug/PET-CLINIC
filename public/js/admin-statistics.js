document.addEventListener("DOMContentLoaded", async () => {
    initPage();

    const inputTuNgay = document.getElementById("tu_ngay");
    const inputDenNgay = document.getElementById("den_ngay");
    const errorBox = document.getElementById("thongKeError");
    let revenueChart = null;
    let currentRevenueData = null;

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
            currentRevenueData = data;

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

                const actions = document.createElement("td");
                actions.className = "text-center text-nowrap";
                const exportButton = document.createElement("button");
                exportButton.type = "button";
                exportButton.className = "btn btn-sm btn-outline-success mr-1";
                exportButton.textContent = "Xuất";
                exportButton.addEventListener("click", () => exportRevenueCsv(
                    [row],
                    "doanh-thu-" + (row.loai_chiphi || "khac").toLowerCase() + ".csv",
                    "Doanh thu " + (categoryNames[row.loai_chiphi] || row.loai_chiphi || "Khác")
                ));
                const printButton = document.createElement("button");
                printButton.type = "button";
                printButton.className = "btn btn-sm btn-outline-dark";
                printButton.textContent = "In";
                printButton.addEventListener("click", () => printRevenueReport(
                    "Doanh thu " + (categoryNames[row.loai_chiphi] || row.loai_chiphi || "Khác"),
                    [row]
                ));
                actions.append(exportButton, printButton);
                tr.appendChild(actions);
                return tr;
            }, 4);

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



    const csvEscape = (value) => '"' + String(value ?? "").replace(/"/g, '""') + '"';

    const exportRevenueCsv = (rows, filename, title) => {
        const lines = [
            [title, "Từ ngày", currentRevenueData?.tu_ngay || "", "Đến ngày", currentRevenueData?.den_ngay || ""],
            ["Loại doanh thu", "Số hóa đơn", "Doanh thu (VND)"],
            ...rows.map(row => [
                categoryNames[row.loai_chiphi] || row.loai_chiphi || "Khác",
                Number(row.so_hoa_don || 0),
                Number(row.tong_doanh_thu || 0)
            ])
        ];
        const csv = "\uFEFF" + lines.map(line => line.map(csvEscape).join(",")).join("\r\n");
        const blob = new Blob([csv], {type: "text/csv;charset=utf-8;"});
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    };

    const printRevenueReport = (title, rows, includeSummary = false) => {
        const data = currentRevenueData || {};
        const rowHtml = rows.map(row => `
            <tr>
                <td>${escapeHtml(categoryNames[row.loai_chiphi] || row.loai_chiphi || "Khác")}</td>
                <td class="number">${Number(row.so_hoa_don || 0).toLocaleString("vi-VN")}</td>
                <td class="number">${formatCurrency(row.tong_doanh_thu || 0)}</td>
            </tr>`).join("");
        const summary = includeSummary ? `
            <p><strong>Tổng doanh thu:</strong> ${formatCurrency(data.tong_doanh_thu || 0)}</p>
            <p><strong>Số hóa đơn đã thanh toán:</strong> ${Number(data.so_hoa_don || 0).toLocaleString("vi-VN")}</p>
            <p><strong>Trung bình / hóa đơn:</strong> ${formatCurrency(data.doanh_thu_trung_binh || 0)}</p>` : "";
        const popup = window.open("", "_blank", "width=900,height=700");
        if (!popup) {
            alert("Trình duyệt đang chặn cửa sổ in. Vui lòng cho phép pop-up rồi thử lại.");
            return;
        }
        popup.document.write(`<!doctype html><html lang="vi"><head><meta charset="utf-8"><title>${escapeHtml(title)}</title>
            <style>body{font-family:Arial,sans-serif;padding:24px;color:#222}h2{text-align:center}
            table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #999;padding:9px}
            th{background:#eee}.number{text-align:right}.meta{margin:8px 0}</style></head><body>
            <h2>${escapeHtml(title)}</h2>
            <p class="meta">Thời gian: ${escapeHtml(formatDate(data.tu_ngay))} - ${escapeHtml(formatDate(data.den_ngay))}</p>
            ${summary}
            <table><thead><tr><th>Loại doanh thu</th><th>Số hóa đơn</th><th>Doanh thu</th></tr></thead>
            <tbody>${rowHtml || '<tr><td colspan="3">Không có dữ liệu</td></tr>'}</tbody></table>
            <script>window.onload=()=>window.print();<\/script></body></html>`);
        popup.document.close();
    };

    const escapeHtml = (value) => String(value ?? "").replace(/[&<>"']/g, char => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    })[char]);

    const exportConsolidatedRevenue = () => {
        if (!currentRevenueData) return alert("Chưa có dữ liệu doanh thu để xuất.");
        const rows = currentRevenueData.theo_loai_chi_phi || [];
        const csvRows = [
            ["BÁO CÁO TỔNG HỢP DOANH THU"],
            ["Từ ngày", currentRevenueData.tu_ngay, "Đến ngày", currentRevenueData.den_ngay],
            ["Tổng doanh thu", Number(currentRevenueData.tong_doanh_thu || 0)],
            ["Số hóa đơn đã thanh toán", Number(currentRevenueData.so_hoa_don || 0)],
            ["Trung bình / hóa đơn", Number(currentRevenueData.doanh_thu_trung_binh || 0)],
            [],
            ["Loại doanh thu", "Số hóa đơn", "Doanh thu (VND)"],
            ...rows.map(row => [
                categoryNames[row.loai_chiphi] || row.loai_chiphi || "Khác",
                Number(row.so_hoa_don || 0),
                Number(row.tong_doanh_thu || 0)
            ]),
            [],
            ["Ngày", "Số hóa đơn", "Doanh thu (VND)"],
            ...(currentRevenueData.theo_ngay || []).map(row => [
                formatDate(row.ngay), Number(row.so_hoa_don || 0), Number(row.tong_doanh_thu || 0)
            ])
        ];
        const csv = "\uFEFF" + csvRows.map(line => line.map(csvEscape).join(",")).join("\r\n");
        const blob = new Blob([csv], {type: "text/csv;charset=utf-8;"});
        const url = URL.createObjectURL(blob);
        const link = document.createElement("a");
        link.href = url;
        link.download = `bao-cao-doanh-thu-${currentRevenueData.tu_ngay}-${currentRevenueData.den_ngay}.csv`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    };

    const printConsolidatedRevenue = () => {
        if (!currentRevenueData) return alert("Chưa có dữ liệu doanh thu để in.");
        printRevenueReport("BÁO CÁO TỔNG HỢP DOANH THU", currentRevenueData.theo_loai_chi_phi || [], true);
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
    document.getElementById("btnXuatTongHop").addEventListener("click", exportConsolidatedRevenue);
    document.getElementById("btnInTongHop").addEventListener("click", printConsolidatedRevenue);
    await Promise.all([loadStatistics(), loadInventoryAlerts()]);
});
