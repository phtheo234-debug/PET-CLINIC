document.addEventListener("DOMContentLoaded", async () => {
    initPage();

    try {
        const res = await apiRequest("/analytics/dashboard");
        const stats = res.data || res;

        document.getElementById("so_ca_kham").innerText =
            stats.so_ca_kham_ngay ?? 0;
        document.getElementById("so_thu_noi_tru").innerText =
            stats.so_thu_noi_tru ?? 0;
        document.getElementById("so_long_trong").innerText =
            stats.so_long_trong ?? 0;
        const revenueElement = document.getElementById("doanh_thu_ngay");
        if (revenueElement) {
            revenueElement.innerText = formatCurrency(stats.doanh_thu_ngay ?? 0);
        }
    } catch (err) {
        alert("Không thể tải dữ liệu Dashboard: " + err.message);
    }
});
