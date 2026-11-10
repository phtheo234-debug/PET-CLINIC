<?php
require_once 'InventoryController.php';

class MedicalController {
    private $pdo;
    private $inventoryController;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->inventoryController = new InventoryController($pdo);
    }

    // API Kê đơn thuốc tính theo mg/kg
    public function createPrescription($data) {
        $pet_id = $data['pet_id'];
        $medical_record_id = $data['medical_record_id'];
        $medicine_id = $data['medicine_id'];
        $doses_per_day = $data['doses_per_day']; // Số lần uống/ngày
        $total_days = $data['total_days'];       // Số ngày uống

        // 1. Lấy cân nặng thực tế của thú cưng từ phiếu khám
        $stmtRecord = $this->pdo->prepare("SELECT weight FROM medical_records WHERE id = ?");
        $stmtRecord->execute([$medical_record_id]);
        $record = $stmtRecord->fetch(PDO::FETCH_ASSOC);
        $weight = $record['weight']; // Ví dụ: 10 kg

        // 2. Lấy thông tin cấu hình liều chuẩn của thuốc (mg/kg và mg/viên)
        $stmtMed = $this->pdo->prepare("SELECT * FROM medicines WHERE id = ?");
        $stmtMed->execute([$medicine_id]);
        $medicine = $stmtMed->fetch(PDO::FETCH_ASSOC);
        
        $mg_per_kg = $medicine['standard_dosage_mg_per_kg']; // Ví dụ: 5mg cho mỗi 1kg
        $mg_per_unit = $medicine['mg_per_unit'];             // Ví dụ: 1 viên chứa 25mg

        // 3. Tính toán công thức
        $total_mg_per_dose = $weight * $mg_per_kg; // Tổng mg cho 1 lần uống
        $total_units_needed = ceil(($total_mg_per_dose / $mg_per_unit) * $doses_per_day * $total_days);

        try {
            $this->pdo->beginTransaction();

            // 4. Trừ kho tự động theo FEFO
            $this->inventoryController->exportMedicineFEFO($medicine_id, $total_units_needed);

            // 5. Lưu vào bảng đơn thuốc (prescriptions)
            $insert = $this->pdo->prepare("INSERT INTO prescriptions (medical_record_id, medicine_id, quantity, usage_instruction) VALUES (?, ?, ?, ?)");
            $insert->execute([
                $medical_record_id,
                $medicine_id,
                $total_units_needed,
                "Uống {$doses_per_day} lần/ngày, mỗi lần " . ($total_mg_per_dose / $mg_per_unit) . " viên"
            ]);

            $this->pdo->commit();
            return ['status' => true, 'message' => 'Kê đơn thành công!', 'total_units' => $total_units_needed];

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }
}
?>