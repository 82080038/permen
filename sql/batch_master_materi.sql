USE skd_cat_bkn;

-- ============================================================
-- MASTER MATERI (Kisi-kisi untuk AI Generator & Acuan Belajar)
-- Berdasarkan Permen PANRB No. 20/2021 & KepmenPANRB No. 208/2025
-- ============================================================

INSERT INTO master_materi (subtes, tipe, topik, kisi_kisi, level) VALUES
-- TWK
('TWK', NULL, 'Nasionalisme', 'Menguji kesadaran berbangsa dan bernegara, semangat cinta tanah air, penggunaan produk dalam negeri, dukungan UMKM, penghormatan simbol negara, peristiwa bersejarah Indonesia (Sumpah Pemuda, Proklamasi, Pertempuran Surabaya), tokoh nasional.', 'sedang'),
('TWK', NULL, 'Integritas', 'Menguji keselarasan pikiran-perkataan-perbuatan, jujur, disiplin, tanggung jawab, menolak suap/gratifikasi, lapor pelanggaran, pencegahan KKN (korupsi, kolusi, nepotisme), etika ASN ber-AKHLAK.', 'sedang'),
('TWK', NULL, 'Bela Negara', 'Menguji kewajiban membela kedaulatan, keutuhan wilayah, keselamatan bangsa (UU No. 3/2002), komponen pertahanan (rakyat, wilayah, SDA), ancaman militer & non-militer, wawasan Nusantara, literasi digital & keamanan siber.', 'sedang'),
('TWK', NULL, 'Pilar Negara', 'Menguji pemahaman Pancasila (5 silah), UUD 1945 (pembukaan, amandemen, lembaga negara), NKRI (kesatuan, Bhinneka Tunggal Ika), hak asasi manusia, demokrasi, rule of law.', 'sedang'),
('TWK', NULL, 'Bahasa Indonesia', 'Menguji EYD (huruf kapital, tanda baca, penulisan kata), kalimat efektif (kesepadanan, keparalelan, kehematan, kelogisan), paragraf (deduktif, induktif, campuran), koherensi, peribahasa & ungkapan.', 'sedang'),

-- TIU Verbal
('TIU', 'verbal', 'Analogi', 'Menguji kemampuan menemukan hubungan logis antara dua pasang kata. Pola: sebab-akibat, fungsi, sinonim, antonim, bagian-keseluruhan, profesi-output, profesi-tempat, habitat, proses, bahan-produk.', 'sedang'),
('TIU', 'verbal', 'Silogisme', 'Menguji penarikan kesimpulan dari dua premis. Pola: universal affirmative (semua A adalah B), universal negative (tidak ada A yang B), particular affirmative (sebagian A adalah B). Hati-hati dengan "sebagian" dan "tidak dapat disimpulkan".', 'sulit'),
('TIU', 'verbal', 'Analitis', 'Menguji kemampuan menyimpulkan dari serangkaian pernyataan. Pola: urutan (lebih tinggi, lebih tua), penempatan posisi, jadwal logis. Gunakan diagram/garis untuk membantu.', 'sedang'),

-- TIU Numerik
('TIU', 'numerik', 'Berhitung', 'Operasi dasar cepat: perkalian 11, 25, 5; kuadrat berakhiran 5; persen, pecahan, proporsi; akar & pangkat. Latihan mental math.', 'mudah'),
('TIU', 'numerik', 'Deret Angka', 'Pola: aritmatika, geometri, kuadratik, pangkat 3, Fibonacci, selisih ganjil/genap, pola grup. Langkah: hitung selisih antar angka, cek apakah beda tetap/naik/teratur.', 'sedang'),
('TIU', 'numerik', 'Perbandingan', 'Perbandingan sebanding (a/b=c/d) dan berbalik nilai (a×b=c×d). Skala, pekerja-waktu, perbandingan kuantitatif.', 'sedang'),
('TIU', 'numerik', 'Soal Cerita', 'Jarak-waktu-kecepatan, diskon, untung-rugi, campuran, sistem persamaan linear. Langkah: identifikasi variabel, tulis rumus, substitusi, perhatikan satuan.', 'sedang'),

-- TIU Figural
('TIU', 'figural', 'Analogi Figural', 'Transformasi gambar: ukuran, jumlah, rotasi, pencerminan, penambahan/pengurangan elemen, pengisian/warna, kombinasi.', 'sedang'),
('TIU', 'figural', 'Ketidaksamaan', 'Cari satu gambar yang berbeda. Pembeda: bentuk dasar, simetri, jumlah elemen, arah putaran, pengisian, posisi.', 'sedang'),
('TIU', 'figural', 'Serial Figural', 'Meneruskan pola gambar. Pola: ukuran naik/turun, jumlah elemen, rotasi bertahap, pengisian bertahap (kosong→setengah→penuh), penambahan garis.', 'sedang'),

-- TKP
('TKP', NULL, 'Pelayanan Publik', 'Menguji sikap empati, responsif, ramah, tanggung jawab terhadap masyarakat. Prinsip SOLID (Speed, Ownership, Loyalty, Integrity, Discipline). Skor tertinggi: langsung membantu, minta maaf jika salah, solusi konkret.', 'sedang'),
('TKP', NULL, 'Jejaring Kerja', 'Menguji kemampuan kerja sama tim, komunikasi terbuka, koordinasi, kolaborasi, resolusi konflik. Skor tertinggi: menghubungi anggota bermasalah, diskusi konstruktif, cari solusi win-win.', 'sedang'),
('TKP', NULL, 'Sosial Budaya', 'Menguji toleransi, empati, inklusi, adaptasi terhadap keberagaman SARA. Skor tertinggi: menghormati adat, membela korban bullying, menerima kehidupan sederhana.', 'sedang'),
('TKP', NULL, 'Teknologi Informasi', 'Menguji adaptabilitas teknologi baru, literasi digital (hoaks vs fakta), keamanan siber, etika medsos. Skor tertinggi: belajar aplikasi baru, klarifikasi hoaks, reset password prosedural.', 'sedang'),
('TKP', NULL, 'Profesionalisme', 'Menguji integritas, disiplin, tanggung jawab, fleksibilitas, etika ASN ber-AKHLAK. Skor tertinggi: tolak suap tegas, terima kritik konstruktif, selesaikan tugas di luar job desk.', 'sedang');
