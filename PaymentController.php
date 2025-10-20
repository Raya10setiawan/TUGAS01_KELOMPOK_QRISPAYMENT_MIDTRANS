<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function index()
    {
        return view('payment'); // resources/views/payment.blade.php
    }

    public function process(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'amount' => 'required|integer|min:1000',
            'payment_type' => 'required|string|in:gopay,qris',
        ]);

        $serverKey = config('services.midtrans.server_key');
        $isProduction = config('services.midtrans.is_production', false);

        $baseUrl = $isProduction
            ? 'https://api.midtrans.com/v2/charge'
            : 'https://api.sandbox.midtrans.com/v2/charge';

        $orderId = 'ORDER-' . Str::uuid();

        // Default payload
        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $request->amount,
            ],
            'customer_details' => [
                'first_name' => $request->name,
                'email' => $request->email,
            ],
        ];

        // Tambahkan tipe pembayaran spesifik
        if ($request->payment_type === 'gopay') {
            $payload['payment_type'] = 'gopay';
            $payload['gopay'] = [
                'enable_callback' => true,
                'callback_url' => url('/payment/success'),
            ];
        } elseif ($request->payment_type === 'qris') {
            $payload['payment_type'] = 'qris';
            $payload['qris'] = [
                'acquirer' => 'gopay', // wajib ada
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . base64_encode($serverKey . ':'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($baseUrl, $payload);

            $body = $response->json();

            if ($response->failed() || isset($body['status_code']) && $body['status_code'] >= 400) {
                Log::error('Midtrans charge failed', ['response' => $body]);
                return response()->json([
                    'status' => 'error',
                    'status_message' => $body['status_message'] ?? 'Payment failed',
                    'data' => $body,
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'data' => $body,
            ]);

        } catch (\Exception $e) {
            Log::error('Midtrans charge exception', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'status_message' => $e->getMessage(),
            ], 500);
        }
    }
}
