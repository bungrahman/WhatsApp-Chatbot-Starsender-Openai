
# Webhook untuk Chatbot dengan OpenAI dan Starsender

Repository ini berisi kode PHP untuk membuat webhook yang mengintegrasikan **OpenAI API** dengan **Starsender API**. Webhook ini dirancang untuk menangani percakapan dengan pengguna melalui WhatsApp menggunakan Starsender, dengan kemampuan untuk mempertahankan riwayat percakapan dan memberikan respons berdasarkan kata kunci atau menggunakan kecerdasan buatan OpenAI.

## Fitur Utama

1. **Integrasi dengan Starsender API**:
   - Menerima pesan dari pengguna melalui webhook Starsender.
   - Mengirim balasan ke pengguna melalui API Starsender.

2. **Pemrosesan Pesan Berdasarkan Kata Kunci**:
   - Mengecek apakah pesan mengandung kata kunci yang telah ditentukan.
   - Memberikan respons otomatis berdasarkan kata kunci yang ditemukan.

3. **Integrasi dengan OpenAI API**:
   - Jika pesan tidak mengandung kata kunci, webhook akan mengirim pesan ke OpenAI untuk menghasilkan respons yang lebih kontekstual.
   - Mendukung model OpenAI seperti `gpt-4.0-mini`.

4. **Manajemen Riwayat Percakapan**:
   - Menyimpan riwayat percakapan dalam file JSON untuk setiap pengguna.
   - Membatasi riwayat percakapan hingga 10 pesan terakhir untuk menjaga kinerja.
   - Menghapus file riwayat percakapan setelah mencapai batas 10 pesan.

5. **Notifikasi Batas Riwayat**:
   - Memberikan notifikasi kepada pengguna ketika riwayat percakapan mencapai batas 10 pesan.
   - Menghapus file riwayat percakapan setelah notifikasi dikirim.

6. **Penanganan Kesalahan**:
   - Menangani kesalahan saat mengirim permintaan ke OpenAI atau Starsender.
   - Menyediakan pesan default jika terjadi kesalahan.

## Cara Kerja

1. **Menerima Pesan**:
   - Webhook menerima pesan dari pengguna melalui Starsender API.
   - Pesan diproses untuk mengecek apakah mengandung kata kunci.

2. **Pemrosesan Kata Kunci**:
   - Jika pesan mengandung kata kunci, webhook akan memberikan respons yang telah ditentukan.
   - Jika tidak, pesan akan diteruskan ke OpenAI untuk diproses.

3. **Pemrosesan dengan OpenAI**:
   - Webhook mengirim riwayat percakapan (termasuk pesan sistem dan pesan pengguna) ke OpenAI.
   - OpenAI menghasilkan respons berdasarkan konteks percakapan.

4. **Penyimpanan Riwayat**:
   - Riwayat percakapan disimpan dalam file JSON dengan nama file berdasarkan nomor pengirim.
   - Riwayat dibatasi hingga 10 pesan terakhir.

5. **Notifikasi dan Penghapusan Riwayat**:
   - Jika riwayat mencapai 10 pesan, webhook akan mengirim notifikasi kepada pengguna.
   - File riwayat percakapan akan dihapus setelah notifikasi dikirim.

6. **Mengirim Balasan**:
   - Webhook mengirim balasan ke pengguna melalui Starsender API.

## Struktur File

- **`index.php`**: File utama yang berisi logika webhook.
- **`responses.json`**: File JSON yang berisi daftar kata kunci dan respons yang sesuai.
- **`history/`**: Direktori untuk menyimpan file riwayat percakapan dalam format JSON.

## Cara Menggunakan

1. **Clone Repository**:
   ```bash
   git clone https://github.com/bungrahman/WhatsApp-Chatbot-Starsender-Openai.git
   ```

2. **Konfigurasi**:
   - Ganti `YOUR_OPENAI_API_KEY` dengan API key OpenAI Anda.
   - Ganti `YOUR_STARSENDER_API_KEY` dengan API key Starsender Anda.

3. **Deploy**:
   - Upload kode ke server web Anda (misalnya, menggunakan cPanel atau FTP).
   - Pastikan direktori `history/` dapat ditulis (writeable).

4. **Atur Webhook di Starsender**:
   - Atur URL webhook di dashboard Starsender ke URL tempat kode ini dihosting.

5. **Uji Coba**:
   - Kirim pesan ke nomor yang terhubung dengan Starsender untuk menguji fungsionalitas.

## Contoh Respons

- **Pesan dengan Kata Kunci**:
  - Pengguna: "info"
  - Bot: "Ini adalah bot WhatsApp yang dibuat oleh bungrahman."

- **Pesan Tanpa Kata Kunci**:
  - Pengguna: "Ceritakan tentang AI."
  - Bot: "AI adalah teknologi yang memungkinkan mesin untuk belajar dan berpikir seperti manusia..."

- **Notifikasi Batas Riwayat**:
  - Bot: "Riwayat percakapan telah mencapai batas 10 pesan. Riwayat lama akan dihapus."

## Kontribusi

Jika Anda ingin berkontribusi pada proyek ini, silakan buka **Pull Request** atau laporkan masalah di **Issues**.

## Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).

---


