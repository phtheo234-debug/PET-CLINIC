<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API Pet Clinic - Bootstrap 4</title>
    <!-- Nhúng CSS chuẩn của Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

    <div class="container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4>Trang Test API Backend (TV 3 - Phong)</h4>
            </div>
            <div class="card-body">
                
                <!-- Test API Kê đơn mg/kg -->
                <div class="form-group">
                    <label><strong>1. Test Kê đơn thuốc (Module 5)</strong></label>
                    <button class="btn btn-success btn-block" onclick="testPrescription()">Gửi API Kê Đơn</button>
                </div>

                <hr>

                <!-- Test API Thanh toán POS -->
                <div class="form-group">
                    <label><strong>2. Test Thanh toán POS & Xuất viện (Module 7)</strong></label>
                    <button class="btn btn-danger btn-block" onclick="testCheckout()">Gửi API Thanh Toán</button>
                </div>

                <hr>
                
                <div class="form-group">
                    <label><strong>Kết quả trả về từ API:</strong></label>
                    <pre id="result" class="bg-dark text-white p-3 rounded" style="min-height: 100px;">Chưa có dữ liệu...</pre>
                </div>

            </div>
        </div>
    </div>

    <!-- Đoạn Script Javascript gọi API Backend -->
    <script>
        // Lấy Token đăng nhập trước (nếu hệ thống bắt buộc Sanctum)
        // Hoặc bạn có thể tạm thời bỏ middleware 'auth:sanctum' ở file api.php nếu muốn test nhanh không cần token.

        function testPrescription() {
            let data = {
                "pet_id": 1,
                "medical_record_id": 1,
                "medicine_id": 1,
                "doses_per_day": 2,
                "total_days": 3
            };

            fetch('/api/v1/medical/prescriptions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                    // 'Authorization': 'Bearer ' + token_của_bạn
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('result').innerText = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                document.getElementById('result').innerText = 'Lỗi: ' + error;
            });
        }

        function testCheckout() {
            let data = {
                "pet_id": 1,
                "payment_method": "Cash",
                "discount": 0,
                "items": [
                    { "type": "Medicine", "id": 1, "quantity": 2, "unit_price": 50000 },
                    { "type": "Service", "id": 1, "quantity": 1, "unit_price": 150000 }
                ]
            };

            fetch('/api/v1/payment/checkout', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('result').innerText = JSON.stringify(data, null, 2);
            })
            .catch(error => {
                document.getElementById('result').innerText = 'Lỗi: ' + error;
            });
        }
    </script>

</body>
</html>