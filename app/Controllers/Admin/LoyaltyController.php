<?php
namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Session;
use App\Core\Database;
use App\Helpers\Loyalty;
use App\Models\Customer;
use App\Models\Setting;

class LoyaltyController extends AdminBaseController
{
    public function index(): void
    {
        $db        = Database::getInstance();
        $customers = $db->query(
            "SELECT c.*, lp_last.balance_after as points_check
             FROM customers c
             LEFT JOIN (
               SELECT customer_id, balance_after
               FROM loyalty_points lp1
               WHERE id = (SELECT MAX(id) FROM loyalty_points WHERE customer_id = lp1.customer_id)
             ) lp_last ON lp_last.customer_id = c.id
             ORDER BY c.loyalty_points DESC"
        )->fetchAll();

        $cfg = Loyalty::config();

        $this->view('loyalty/index', [
            'pageTitle' => 'Puntos de fidelidad',
            'customers' => $customers,
            'cfg'       => $cfg,
        ]);
    }

    public function adjust(): void
    {
        Csrf::verifyOrFail();

        $customerId = (int)$this->input('customer_id');
        $points     = (int)$this->input('points', 0);
        $reason     = $this->input('reason', 'Ajuste manual');

        if (!$customerId || $points === 0) {
            $this->json(['success' => false, 'message' => 'Datos inválidos.'], 400);
        }

        Loyalty::adjust($customerId, $points, $reason);

        $db      = Database::getInstance();
        $balance = (int)$db->query("SELECT loyalty_points FROM customers WHERE id = ?", [$customerId])->fetchColumn();

        $this->json([
            'success' => true,
            'balance' => $balance,
            'tier'    => Loyalty::tierLabel(Loyalty::tier($balance, Loyalty::config())),
        ]);
    }

    public function config(): void
    {
        if ($this->isPost()) {
            Csrf::verifyOrFail();
            $s = new Setting();
            $s->saveMany([
                'loyalty_enabled'        => $this->input('loyalty_enabled', '0'),
                'loyalty_points_per_peso'=> $this->input('loyalty_points_per_peso', '1'),
                'loyalty_redeem_rate'    => $this->input('loyalty_redeem_rate', '100'),
                'loyalty_min_redeem'     => $this->input('loyalty_min_redeem', '500'),
                'loyalty_silver_min'     => $this->input('loyalty_silver_min', '5000'),
                'loyalty_gold_min'       => $this->input('loyalty_gold_min', '15000'),
                'loyalty_plat_min'       => $this->input('loyalty_plat_min', '40000'),
            ]);
            Session::flash('success', 'Configuración de puntos guardada.');
            $this->redirect('admin/puntos');
        }
        $this->redirect('admin/puntos');
    }
}
