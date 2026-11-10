<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;

class PaymentController extends Controller
{
    private $pdo;

    public function __construct()
    {
        // Khởi tạo kết nối PDO từ Laravel Database Manager
        $this->pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
    }

    // API Lập hóa đơn POS và Thanh toán xuất viện
    public function createInvoiceAndCheckout(Request $request)
    {
        $data = $request->all();
        $pet_id = $data['pet_id'] ?? null;
        $items = $data['items'] ?? []; // Mảng danh sách các khoản phí (thuốc, dịch vụ, lồng, phẫu thuật...)
        $payment_method = $data['payment_method'] ?? 'Cash'; // Tiền mặt / Chuyển khoản
        $discount = $data['discount'] ?? 0;

        try {
            $this->pdo->beginTransaction();

            $total_amount = 0;
            foreach ($items as $item) {
                $total_amount += $item['quantity'] * $item['unit_price'];
            }
            
            // Sửa lỗi cú pháp tính tiền tại đây:
            $final_amount = $total_amount - $discount;

            // 1. Lưu hóa đơn chính
            $stmtInv = $this->pdo->prepare("INSERT INTO invoices (pet_id, total_amount, discount, final_amount, payment_method, status, created_at) VALUES (?, ?, ?, ?, ?, 'Paid', NOW())");
            $stmtInv->execute([$pet_id, $total_amount, $discount, $final_amount, $payment_method]);
            $invoice_id = $this->pdo->lastInsertId();

            // 2. Lưu chi tiết các khoản phí vào invoice_details
            foreach ($items as $item) {
                $stmtDetail = $this->pdo->prepare("INSERT INTO invoice_details (invoice_id, item_type, item_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $stmtDetail->execute([
                    $invoice_id,
                    $item['type'], // 'Medicine', 'Service', 'Surgery', 'Boarding'
                    $item['id'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['quantity'] * $item['unit_price']
                ]);
            }

            // 3. Cập nhật trạng thái thú cưng thành DISCHARGED (Đã xuất viện)
            $updatePet = $this->pdo->prepare("UPDATE pets SET status = 'DISCHARGED' WHERE id = ?");
            $updatePet->execute([$pet_id]);

            // 4. Giải phóng lồng lưu trú (Cập nhật lồng về AVAILABLE)
            $updateCage = $this->pdo->prepare("UPDATE cages SET status = 'AVAILABLE', current_pet_id = NULL WHERE current_pet_id = ?");
            $updateCage->execute([$pet_id]);

            $this->pdo->commit();
            return response()->json(['status' => true, 'invoice_id' => $invoice_id, 'message' => 'Thanh toán và xuất viện thành công!']);

        } catch (Exception $e) {
            $this->pdo->rollBack();
            return response()->json(['status' => false, 'message' => 'Lỗi thanh toán: ' . $e->getMessage()], 500);
        }
    }
}