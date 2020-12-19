<?php

namespace App\Http\Controllers\API;

use Midtrans\Config;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Auth;
use Midtrans\Snap;

class TransactionController extends Controller
{
    public function all(Request $request) 
    {

        // INI ADALAH VARIABELNYA
        $id = $request->input('id');
        $limit = $request->input('limit', 6);
        $food_id = $request->input('food_id');
        $status = $request->input('status');

        if ($id) 
        {

            $transaction = Transaction::with(['food','user'])->find($id);

            if ($transaction) 
            {
                return ResponseFormatter::success(
                    $transaction,
                    'Data transaksi berhasil diambil'
                );
            }
            else 
            {
                return ResponseFormatter::error(
                    null,
                    'Data transaksi tidak ada',
                    404
                );
            }
        }

        $transaction = Transaction::with(['food','user'])
        ->where('user_id', Auth::user()->id);

        if ($food_id)
        {
            $transaction->where('food_id',$food_id);
        }

        if($status) 
        {
            $transaction->where('status',$status);
        }

        return ResponseFormatter::success(
            $transaction->paginate($limit),
            'Data list produk berhasil diambil'
        );
    }

    // YANG INI BISA UNTUK DI KOMENTARI SAJA 
    // KARENA HANYA UNTUK MENGUJI TRANSAKSI
    public function update(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $transaction->update($request->all());

        return ResponseFormatter::success($transaction, 'Transaksi berhasil di perbarui');
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'food_id' => 'required|exists:food,id',
            'user_id' => 'required|exists:users,id',
            'quantity' => 'required',
            'total' => 'required',
            'status' => 'required',
        ]);

        $transaction = Transaction::create([
            'food_id' => $request->food_id,
            'user_id' => $request->user_id,
            'quantity' => $request->quantity,
            'total' => $request->total,
            'status' => $request->status,
            'payment_url' => '',
        ]);

        // KONFIGURASI MIDTRANS
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // PANGGIL TRANSAKSI YANG TADI DI BUAT
        $transaction = Transaction::with(['food','user'])->find($transaction->id);

        // MEMBUAT TRANSAKSI MIDTRANS
        $midtrans = [
            'transaction_details' =>[
                'order_id' => $transaction->id,
                'gross_amount' => (int) $transaction->total,
            ],
            'customer_details' => [
                'first_name' => $transaction->user->name,
                'email' => $transaction->user->email,
            ],
            'enabled_payments' => ['gopay','bank_transfer'],
            'vtweb' => array()
        ];

        // MEMANGGIL MIDTRANS

        try {
            //AMBIL HALAMAN PAYMENTS MIDTRANS 
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url; 

            $transaction->payment_url = $paymentUrl;
            $transaction->save();
            
            // MENGEMBALIKAN DATA KE API
            return ResponseFormatter::success($transaction, 'Transaksi berhasil');

        } catch (Exception $e) {
            return ResponseFormatter::error($e->getMessage(), 'Transaksi gagal');
        }

    }
}
