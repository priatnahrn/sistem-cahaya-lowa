kasir
- bisa melakukan scanning nota penjualan yang dilakukan melalui barcode nya
- hasil scanning bisa membaca total pembayaran yang harus dibayar
- ngescan buattt munculin invoicenya berarti semua data di penjualan akan muncul
- nanti bakal ada fitur untuk membayar di kasir
- pilih metode pembayaran
- masukkan nominal yang dibayarkan
- jika nominal yang dibayarkan kurang, masukkan ke daftar tagihan
- jika nominal yang dibayarkan lebih, muncul jumlah kembalian
- jika nominal pas, muncul kembalian 0
- setelah itu, muncul pop up selesai pembayaran
- masuk ke daftar transaksi di kasir

action 
- lihat detail
- tambah transaksi 
    - scanning
    - manual
- tambah pembayaran
 - melalui scanning
 - melalui manual


datatable
- nomor
- nomor faktur
- tanggal pembayaran
- pelanggan
- total pembayaran
- status: [ belum lunas, lunas ]
    - jika lunas, selesai
    - jika belum lunas, masuk ke daftar tagihan
- action lihat detail