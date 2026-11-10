<?php
class InventoryController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Hàm xuất kho tự động theo nguyên tắc FEFO (First Expired, First Out)
    public function exportMedicineFEFO($medicine_id, $required_qty) {
        // Lấy danh sách các lô còn hàng, sắp xếp hạn sử dụng tăng dần (gần hết hạn lên đầu)
        $stmt = $this->pdo->prepare("SELECT * FROM inventory_batches WHERE medicine_id = ? AND quantity > 0 ORDER BY expiry_date ASC");
        $stmt->execute([$medicine_id]);
        $batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $remaining_qty = $required_qty;
        $export_details = [];

        foreach ($batches as $batch) {
            if ($remaining_qty <= 0) break;

            if ($batch['quantity'] >= $remaining_qty) {
                $take_qty = $remaining_qty;
                $new_batch_qty = $batch['quantity'] - $required_qty;
                $remaining_qty = 0;
            } else {
                $take_qty = $batch['quantity'];
                $new_batch_qty = 0;
                $remaining_qty -= $batch['quantity'];
            }

            // Cập nhật lại số lượng lô trong CSDL
            $update = $this->pdo->prepare("UPDATE inventory_batches SET quantity = ? WHERE id = ?");
            $update->execute([$new_batch_qty, $batch['id']]);

            $export_details[] = [
                'batch_id' => $batch['id'],
                'batch_code' => $batch['batch_code'],
                'taken_quantity' => $take_qty
            ];
        }

        if ($remaining_qty > 0) {
            throw new Exception("Kho không đủ số lượng thuốc yêu cầu! Còn thiếu: " . $remaining_qty);
        }

        return $export_details;
    }
}
?>