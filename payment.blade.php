<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <title>Pembayaran Midtrans (GoPay & QRIS)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-gray-100 via-gray-200 to-gray-300">

  <div class="w-full max-w-md bg-white rounded-3xl shadow-xl overflow-hidden border border-gray-100">
    <!-- Header -->
    <div class="bg-gradient-to-r from-indigo-600 to-blue-500 text-white text-center py-6">
      <h1 class="text-2xl font-bold tracking-wide">Pembayaran Online</h1>
      <p class="text-sm opacity-90 mt-1">GoPay & QRIS | Midtrans</p>
    </div>

    <!-- Form -->
    <div class="p-6">
      <div class="mb-4">
        <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Nama</label>
        <input id="name" type="text" placeholder="Masukkan nama lengkap"
               class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
      </div>

      <div class="mb-4">
        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
        <input id="email" type="email" placeholder="Masukkan email"
               class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
      </div>

      <div class="mb-6">
        <label for="amount" class="block text-sm font-semibold text-gray-700 mb-1">Jumlah (IDR)</label>
        <input id="amount" type="number" min="1000" placeholder="Minimal Rp 10.000"
               class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none">
      </div>

      <!-- Buttons -->
      <div class="space-y-3">
        <button onclick="pay('gopay')" class="w-full py-3 font-bold text-white rounded-xl bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 shadow-md transition-transform transform hover:-translate-y-0.5">
          Bayar dengan GoPay
        </button>

        <button onclick="pay('qris')" class="w-full py-3 font-bold text-white rounded-xl bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 shadow-md transition-transform transform hover:-translate-y-0.5">
          Bayar dengan QRIS
        </button>
      </div>

      <!-- Result -->
      <div id="result" class="mt-6"></div>
    </div>

    <!-- Footer -->
    <div class="bg-gray-50 py-3 text-center text-xs text-gray-500 border-t">
       {{ date('Y') }} Pembayaran Midtrans
    </div>
  </div>

  <!-- Script -->
  <script>
    async function pay(type) {
      const name = document.getElementById("name").value.trim();
      const email = document.getElementById("email").value.trim();
      const amount = parseInt(document.getElementById("amount").value);
      const resultDiv = document.getElementById("result");

      if (!name || !email || !amount || amount < 1000) {
        resultDiv.innerHTML = `
          <div class="bg-red-100 text-red-700 border border-red-400 px-4 py-3 rounded-xl font-medium mt-2">
            ⚠️ Mohon isi semua data dengan benar.
          </div>`;
        return;
      }

      resultDiv.innerHTML = `
        <div class="text-center py-4">
          <div class="w-10 h-10 mx-auto border-4 border-gray-300 border-t-indigo-600 rounded-full animate-spin mb-2"></div>
          <p class="text-gray-600 font-medium">Memproses pembayaran...</p>
        </div>`;

      try {
        const res = await fetch(window.location.origin + "/payment/process", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({ name, email, amount, payment_type: type }),
        });

        const data = await res.json();

        if (data.status === "success") {
          if (type === "gopay" && data.data.actions) {
            const deeplink = data.data.actions.find(a => a.name === "deeplink-redirect");
            if (deeplink?.url) {
              window.location.href = deeplink.url;
            } else {
              resultDiv.innerHTML = `<div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded-xl">⚠️ Link GoPay tidak tersedia.</div>`;
            }
          } else if (type === "qris" && data.data.qr_string) {
            const qrUrl = data.data.qr_string;
            resultDiv.innerHTML = `
              <div class="text-center">
                <p class="font-semibold text-gray-800 mb-3">📱 Scan QRIS untuk membayar:</p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(qrUrl)}" 
                     alt="QRIS Code" class="mx-auto rounded-lg shadow-md border">
                <p class="text-sm text-gray-500 mt-3">Gunakan aplikasi seperti Dana, OVO, ShopeePay, atau GoPay.</p>
              </div>`;
          } else {
            resultDiv.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl">⚠️ Respons tidak dikenali.</div>`;
          }
        } else {
          resultDiv.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl">⚠️ ${data.status_message || "Gagal memproses pembayaran."}</div>`;
        }
      } catch (err) {
        resultDiv.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl">⚠️ Gagal terhubung ke server: ${err.message}</div>`;
      }
    }
  </script>
</body>
</html>
