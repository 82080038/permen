<?php
/*
MATERI TES INTELEGENSIA UMUM (TIU)
Berdasarkan Permen PANRB No. 20 Tahun 2021 & KepmenPANRB No. 208/2025
Substansi: Verbal (Analogi, Silogisme, Analitis), Numerik (Berhitung, Deret, Perbandingan, Cerita), Figural (Analogi, Ketidaksamaan, Serial)
*/
return [
    [
        'id' => 'tiu_verbal_analogi',
        'subtes' => 'TIU',
        'tipe' => 'verbal',
        'judul' => 'Analogi Verbal',
        'konten' => '<h2>A. Analogi Verbal</h2>
<h3>1. Konsep</h3>
<p>Analogi adalah <b>perbandingan hubungan</b> antara dua pasang kata. Format: <code>A : B = C : D</code></p>
<h3>2. Pola Hubungan Umum</h3>
<ul>
<li><b>Sebab-Akibat</b>: Hujan : Banjir = Kebakaran : Abu</li>
<li><b>Fungsi</b>: Matahari : Cahaya = Pompa : Air</li>
<li><b>Sinonim</b>: Bahagia : Gembira = Sedih : Murung</li>
<li><b>Antonim</b>: Panas : Dingin = Atas : Bawah</li>
<li><b>Bagian-Keseluruhan</b>: Roda : Mobil = Daun : Pohon</li>
<li><b>Profesi-Output</b>: Penulis : Buku = Petani : Padi</li>
<li><b>Profesi-Tempat</b>: Dokter : RS = Guru : Sekolah</li>
<li><b>Habitat</b>: Burung : Udara = Ikan : Air</li>
<li><b>Proses</b>: Biji : Pohon = Telur : Ayam</li>
<li><b>Bahan-Produk</b>: Kulit : Sepatu = Kayu : Meja</li>
<li><b>Karakteristik</b>: Singa : Buas = Kelinci : Jinak</li>
<li><b>Instrumen-Ukuran</b>: Termometer : Suhu = Timbangan : Berat</li>
</ul>
<h3>3. Strategi Menjawab</h3>
<ol>
<li>Tentukan hubungan pasangan pertama (A : B).</li>
<li>Cari pasangan kedua (C : D) dengan hubungan <b>persis sama</b>.</li>
<li>Jika ragu, cocokkan satu per satu pola di atas.</li>
<li>Hindari jawaban yang hampir mirip — periksa presisi hubungannya.</li>
</ol>
<h3>4. Contoh & Pembahasan</h3>
<p><b>Soal</b>: BURUNG : UDARA = ... : ...</p>
<ul>
<li>A. Ikan : Air → <b>benar</b> (habitat)</li>
<li>B. Kucing : Tikus → salah (predator-mangsa)</li>
<li>C. Kereta : Rel → salah (benda-jalur)</li>
</ul>
<p><b>Soal</b>: DOCTOR : MEDICINE = CHEF : ...</p>
<ul>
<li>A. Kitchen → tempat kerja</li>
<li>B. Food → <b>output utama</b> (profesi-hasil)</li>
<li>C. Recipe → alat bantu</li>
</ul>'
    ],
    [
        'id' => 'tiu_verbal_silogisme',
        'subtes' => 'TIU',
        'tipe' => 'verbal',
        'judul' => 'Silogisme',
        'konten' => '<h2>B. Silogisme (Penalaran Logis)</h2>
<h3>1. Konsep</h3>
<p>Silogisme adalah <b>penarikan kesimpulan</b> dari dua premis. Rumus:</p>
<ul>
<li><b>Universal Affirmative (A)</b>: Semua A adalah B.</li>
<li><b>Universal Negative (E)</b>: Tidak ada A yang B.</li>
<li><b>Particular Affirmative (I)</b>: Sebagian A adalah B.</li>
<li><b>Particular Negative (O)</b>: Sebagian A bukan B.</li>
</ul>
<h3>2. Pola Umum</h3>
<table border="1" cellpadding="5">
<tr><th>Premis 1</th><th>Premis 2</th><th>Kesimpulan</th></tr>
<tr><td>Semua A adalah B</td><td>Semua B adalah C</td><td>Semua A adalah C</td></tr>
<tr><td>Semua A adalah B</td><td>C adalah A</td><td>C adalah B</td></tr>
<tr><td>Tidak ada A yang B</td><td>Semua C adalah B</td><td>Tidak ada C yang A</td></tr>
<tr><td>Sebagian A adalah B</td><td>Semua B adalah C</td><td>Sebagian A adalah C</td></tr>
</table>
<h3>3. Strategi Menjawab</h3>
<ol>
<li>Gambar diagram lingkaran (Venn) jika perlu.</li>
<li>Hati-hati dengan kata <b>"sebagian"</b> → tidak bisa disimpulkan "semua".</li>
<li>Hati-hati dengan kata <b>"tidak ada"</b> → berarti himpunan terpisah.</li>
<li>Jika premis berisi "sebagian" + "sebagian" → <b>tidak dapat disimpulkan</b>.</li>
</ol>
<h3>4. Contoh & Pembahasan</h3>
<p><b>Soal</b>: Semua mahasiswa Sekolah Kedinasan adalah calon PNS. Budi adalah mahasiswa Sekolah Kedinasan.</p>
<ul>
<li>A. Budi belum tentu calon PNS → salah</li>
<li>B. Budi adalah calon PNS → <b>benar</b></li>
<li>E. Tidak dapat disimpulkan → salah</li>
</ul>
<p><b>Soal</b>: Semua penari adalah atlet. Sebagian atlet adalah vegetarian.</p>
<ul>
<li>E. Tidak dapat disimpulkan → <b>benar</b> (tidak tahu apakah penari ada di bagian vegetarian)</li>
</ul>'
    ],
    [
        'id' => 'tiu_verbal_analitis',
        'subtes' => 'TIU',
        'tipe' => 'verbal',
        'judul' => 'Analitis Verbal',
        'konten' => '<h2>C. Analitis Verbal</h2>
<h3>1. Konsep</h3>
<p>Soal analitis menguji kemampuan menarik kesimpulan dari serangkaian pernyataan. Biasanya berupa: urutan, penempatan, jadwal, atau atribusi.</p>
<h3>2. Pola Umum</h3>
<ul>
<li><b>Urutan</b>: A lebih X dari B. B lebih X dari C → urutan A > B > C.</li>
<li><b>Penempatan</b>: P di sebelah kiri Q, R di ujung → gambar posisi.</li>
<li><b>Jadwal</b>: Jika hari Senin tidak A, maka Selasa B. Hari Senin tidak A → ?</li>
</ul>
<h3>3. Strategi Menjawab</h3>
<ol>
<li>Buat <b>diagram/garis</b> atau tabel sederhana.</li>
<li>Tulis setiap pernyataan sebagai fakta terpisah.</li>
<li>Gabungkan fakta untuk menemukan urutan atau posisi.</li>
<li>Jika ada "tidak diketahui" → jawaban "tidak dapat ditentukan".</li>
</ol>
<h3>4. Contoh & Pembahasan</h3>
<p><b>Soal</b>: Andi > Budi. Budi > Candra. Dedi > Andi. Paling tinggi?</p>
<ul>
<li>Urutan: Dedi > Andi > Budi > Candra → Dedi (D).</li>
</ul>
<p><b>Soal</b>: Andi di kiri Budi, Candra di kanan Budi. Andi paling kiri. Dedi di kanan Candra.</p>
<ul>
<li>Posisi: Andi - Budi - Candra - Dedi → tengah = Budi (B).</li>
</ul>'
    ],
    [
        'id' => 'tiu_numerik_berhitung',
        'subtes' => 'TIU',
        'tipe' => 'numerik',
        'judul' => 'Kemampuan Berhitung',
        'konten' => '<h2>D. Kemampuan Berhitung</h2>
<h3>1. Operasi Dasar Cepat</h3>
<ul>
<li><b>Perkalian 11</b>: 23 × 11 = 2 (2+3) 3 = 253.</li>
<li><b>Perkalian 25</b>: 36 × 25 = 36 × 100 / 4 = 900.</li>
<li><b>Perkalian 5</b>: 48 × 5 = 48 × 10 / 2 = 240.</li>
<li><b>Kuadrat berakhiran 5</b>: 35² = (3×4)25 = 1225.</li>
<li><b>Pembagian 1000/125</b>: 1000/125 = 8 (ingat kelipatan 125).</li>
</ul>
<h3>2. Persen, Pecahan, & Proporsi</h3>
<ul>
<li><b>Persen ke desimal</b>: 15% = 0,15.</li>
<li><b>Pecahan</b>: ¾ = 0,75 = 75%.</li>
<li><b>Proporsi</b>: a/b = c/d → a×d = b×c.</li>
</ul>
<h3>3. Akar & Pangkat</h3>
<ul>
<li>√729 = 27 (karena 27×27 = 729).</li>
<li>2⁵ = 32; 3⁴ = 81; 5³ = 125.</li>
</ul>
<h3>4. Contoh & Pembahasan</h3>
<p><b>Soal</b>: 45% dari 800 = ? → 0,45 × 800 = <b>360</b>.</p>
<p><b>Soal</b>: (25×4)+(125÷5) = ? → 100 + 25 = <b>125</b>.</p>
<p><b>Soal</b>: Jika x=3, y=-2, nilai 2x²-3y = ? → 2(9)+6 = <b>24</b>.</p>'
    ],
    [
        'id' => 'tiu_numerik_deret',
        'subtes' => 'TIU',
        'tipe' => 'numerik',
        'judul' => 'Deret Angka',
        'konten' => '<h2>E. Deret Angka</h2>
<h3>1. Pola Dasar</h3>
<ul>
<li><b>Aritmatika</b>: beda tetap → 2, 5, 8, 11, ... (+3)</li>
<li><b>Geometri</b>: rasio tetap → 3, 6, 12, 24, ... (×2)</li>
<li><b>Kuadratik</b>: beda naik → 1, 4, 9, 16, ... (n²)</li>
<li><b>Pangkat 3</b>: 1, 8, 27, 64, ... (n³)</li>
<li><b>Fibonacci</b>: jumlah dua sebelumnya → 1, 1, 2, 3, 5, 8, ...</li>
<li><b>Pola selisih</b>: beda ganjil/genap → 2, 5, 10, 17, 26 ... (+3,+5,+7,+9)</li>
<li><b>Pola grup</b>: 1,2,1,3,1,4, ... (grup 1 dan naik)</li>
</ul>
<h3>2. Strategi Menjawab</h3>
<ol>
<li>Hitung <b>selisih antar angka</b> (beda).</li>
<li>Jika beda tetap = aritmatika.</li>
<li>Jika beda naik/teratur = kuadratik atau pola selisih.</li>
<li>Jika dibagi/kali tetap = geometri.</li>
<li>Jika tidak jelas, perhatikan selisih kedua (beda dari beda).</li>
</ol>
<h3>3. Contoh & Pembahasan</h3>
<p><b>Soal</b>: 2, 5, 10, 17, 26, ... → beda +3,+5,+7,+9 → selanjutnya +11 → <b>37</b>.</p>
<p><b>Soal</b>: 1, 1, 2, 3, 5, 8, ... → Fibonacci → <b>13</b> (5+8).</p>
<p><b>Soal</b>: 100, 50, 25, 12,5, ... → ÷2 → <b>6,25</b>.</p>'
    ],
    [
        'id' => 'tiu_numerik_perbandingan',
        'subtes' => 'TIU',
        'tipe' => 'numerik',
        'judul' => 'Perbandingan Kuantitatif',
        'konten' => '<h2>F. Perbandingan Kuantitatif</h2>
<h3>1. Rumus Dasar</h3>
<ul>
<li><b>Perbandingan sederhana</b>: A : B = m : n → A = (m/(m+n)) × total.</li>
<li><b>Skala</b>: Skala = jarak gambar / jarak sebenarnya.</li>
<li><b>Perbandingan berbalik nilai</b>: Pekerja × Waktu = tetap. Jika pekerja naik, waktu turun.</li>
</ul>
<h3>2. Strategi</h3>
<ol>
<li>Identifikasi apakah perbandingan <b>sebanding</b> atau <b>berbalik</b>.</li>
<li>Jika sebanding: a/b = c/d.</li>
<li>Jika berbalik: a×b = c×d.</li>
</ol>
<h3>3. Contoh & Pembahasan</h3>
<p><b>Soal</b>: 3 pekerja selesai dalam 6 hari. Berapa hari untuk 9 pekerja?</p>
<ul>
<li>Berbalik: 3×6 = 9×h → h = <b>2 hari</b>.</li>
</ul>
<p><b>Soal</b>: Laki-laki : Perempuan = 3:5. Perempuan 25 orang. Laki-laki?</p>
<ul>
<li>3/5 = x/25 → x = (3×25)/5 = <b>15</b>.</li>
</ul>'
    ],
    [
        'id' => 'tiu_numerik_cerita',
        'subtes' => 'TIU',
        'tipe' => 'numerik',
        'judul' => 'Soal Cerita Numerik',
        'konten' => '<h2>G. Soal Cerita Numerik</h2>
<h3>1. Pola Umum</h3>
<ul>
<li><b>Jarak-Waktu-Kecepatan</b>: J = K × W; K = J/W; W = J/K.</li>
<li><b>Diskon</b>: Harga bayar = Harga awal - (Harga awal × % diskon).</li>
<li><b>Untung-Rugi</b>: Untung = Harga jual - Harga beli. % untung = (untung/beli)×100.</li>
<li><b>Campuran</b>: Gunakan sistem persamaan linear.</li>
</ul>
<h3>2. Strategi</h3>
<ol>
<li>Identifikasi <b>variabel yang diketahui</b> dan <b>yang dicari</b>.</li>
<li>Tulis rumus yang relevan.</li>
<li>Substitusi nilai.</li>
<li>Perhatikan satuan (km/jam, m/menit, persen vs desimal).</li>
</ol>
<h3>3. Contoh & Pembahasan</h3>
<p><b>Soal</b>: Bus berangkat 07.00, tiba 10.30. Jarak 210 km. Kecepatan?</p>
<ul>
<li>Waktu = 3,5 jam. K = 210/3,5 = <b>60 km/jam</b>.</li>
</ul>
<p><b>Soal</b>: Diskon 20% untuk pembelian >100.000. Harga 150.000, bayar?</p>
<ul>
<li>Bayar = 150.000 - (20%×150.000) = 150.000 - 30.000 = <b>120.000</b>.</li>
</ul>
<p><b>Soal</b>: 2a + 3b = 13 dan a + b = 5. Nilai a?</p>
<ul>
<li>b = 5-a. Substitusi: 2a + 3(5-a) = 13 → 2a + 15 - 3a = 13 → -a = -2 → a = <b>2</b>.</li>
</ul>'
    ],
    [
        'id' => 'tiu_figural_analogi',
        'subtes' => 'TIU',
        'tipe' => 'figural',
        'judul' => 'Analogi Figural',
        'konten' => '<h2>H. Analogi Figural</h2>
<h3>1. Konsep</h3>
<p>Analogi figural membandingkan <b>transformasi gambar</b> dari bentuk A ke B, lalu menerapkan transformasi yang sama dari C ke D.</p>
<h3>2. Pola Transformasi</h3>
<ul>
<li><b>Ukuran</b>: kecil → besar, besar → kecil.</li>
<li><b>Jumlah</b>: 1 → 2, 2 → 4 (gandakan).</li>
<li><b>Rotasi</b>: 90° kanan, 180°, 270°.</li>
<li><b>Pencerminan</b>: horizontal, vertikal, diagonal.</li>
<li><b>Penambahan/pengurangan elemen</b>: lingkaran ditambah garis, segitiga ditambah sudut.</li>
<li><b>Pengisian/warna</b>: kosong → arsir, arsir → penuh.</li>
<li><b>Kombinasi</b>: rotasi + pencerminan, ukuran + pengisian.</li>
</ul>
<h3>3. Contoh & Pembahasan</h3>
<p><b>Soal</b>: Segitiga → Segitiga sama sisi. Persegi → ?</p>
<ul>
<li>Transformasi: bentuk dasar → bentuk sempurna/reguler. Jawaban: <b>Persegi dengan semua sisi sama</b>.</li>
</ul>'
    ],
    [
        'id' => 'tiu_figural_ketidaksamaan',
        'subtes' => 'TIU',
        'tipe' => 'figural',
        'judul' => 'Ketidaksamaan Figural',
        'konten' => '<h2>I. Ketidaksamaan Figural</h2>
<h3>1. Konsep</h3>
<p>Cari <b>satu gambar yang berbeda</b> dari kelompok gambar lain yang memiliki pola/karakteristik sama.</p>
<h3>2. Pola Pembeda</h3>
<ul>
<li><b>Bentuk dasar</b>: 4 segi empat, 1 segitiga → segitiga yang berbeda.</li>
<li><b>Simetri</b>: 4 simetris, 1 tidak simetris.</li>
<li><b>Jumlah elemen</b>: 4 punya 3 garis, 1 punya 4 garis.</li>
<li><b>Arah putaran</b>: 4 searah jarum jam, 1 berlawanan.</li>
<li><b>Pengisian</b>: 4 kosong, 1 arsir/penuh.</li>
<li><b>Posisi</b>: 4 di tengah, 1 di sudut.</li>
</ul>
<h3>3. Contoh & Pembahasan</h3>
<p><b>Soal</b>: Dari 5 gambar (segitiga, persegi, lingkaran, persegi panjang, jajaran genjang), yang berbeda?</p>
<ul>
<li><b>Lingkaran</b> — satu-satunya tanpa sudut/garis lurus.</li>
</ul>'
    ],
    [
        'id' => 'tiu_figural_serial',
        'subtes' => 'TIU',
        'tipe' => 'figural',
        'judul' => 'Serial Figural',
        'konten' => '<h2>J. Serial Figural</h2>
<h3>1. Konsep</h3>
<p>Meneruskan <b>urutan/pola</b> pada deretan gambar. Sama seperti deret angka, tapi dalam bentuk visual.</p>
<h3>2. Pola Umum</h3>
<ul>
<li><b>Ukuran naik/turun</b>: kecil → sedang → besar → lebih besar.</li>
<li><b>Jumlah elemen naik/turun</b>: 1 titik → 2 titik → 3 titik.</li>
<li><b>Rotasi bertahap</b>: 0° → 45° → 90° → 135°.</li>
<li><b>Pengisian bertahap</b>: kosong → setengah → penuh → setengah lain → kosong (siklus).</li>
<li><b>Penambahan garis</b>: 1 garis → 2 garis → 3 garis.</li>
<li><b>Pergantian posisi</b>: kiri → tengah → kanan → kiri (siklus).</li>
</ul>
<h3>3. Contoh & Pembahasan</h3>
<p><b>Soal</b>: ○ → ◐ → ● → ?</p>
<ul>
<li>Pola: kosong → setengah kanan → penuh → <b>setengah kiri (◑)</b> → kosong.</li>
</ul>'
    ]
];
