<?php
/**
 * PDF & Document Validation Library
 * Sistem Manajemen Penerimaan Magang BTKP DIY
 * 
 * Bertanggung jawab atas:
 * 1. Validasi teknis file (MIME type, ekstensi, magic bytes, ukuran file, sanitasi nama).
 * 2. Ekstraksi teks PDF murni PHP tanpa dependensi berat eksternal.
 * 3. Klasifikasi konten (Surat Pengantar vs Proposal vs Sertifikat vs Modul vs Scan).
 * 4. Penanganan dokumen opsional (Proposal magang).
 */

class PDFValidator {

    const MAX_FILE_SIZE = 5242880; // 5 MB

    /**
     * Memvalidasi file surat pengantar secara menyeluruh (teknis & isi)
     * 
     * @param string $filepath Path fisik file di server
     * @param string $original_name Nama asli file dari form upload
     * @return array Hasil evaluasi: ['valid_technical', 'status_berkas', 'catatan', 'score', 'detected_type']
     */
    public static function validateSuratPengantar($filepath, $original_name = '') {
        // 1. Validasi Teknis Dasar
        $techCheck = self::validateTechnical($filepath, $original_name);
        if (!$techCheck['valid']) {
            return [
                'valid_technical' => false,
                'status_berkas'   => 'Tidak Valid',
                'catatan'         => $techCheck['message'],
                'score'           => 0,
                'detected_type'   => 'INVALID_FILE',
                'extracted_text'  => ''
            ];
        }

        // 2. Ekstraksi Teks PDF
        $extractedText = self::extractText($filepath);
        $textLen = strlen(trim($extractedText));

        // 3. Deteksi Dokumen Hasil Scan
        if ($textLen < 25) {
            // Periksa apakah dokumen memiliki gambar/XObject (hasil scan)
            $isScanned = self::hasImageStreams($filepath);
            return [
                'valid_technical' => true,
                'status_berkas'   => 'Perlu Pemeriksaan Manual',
                'catatan'         => $isScanned 
                    ? 'Dokumen terdeteksi berupa hasil scan (gambar). Diperlukan verifikasi manual oleh petugas.' 
                    : 'Teks dokumen tidak dapat diekstrak atau dokumen kosong. Memerlukan verifikasi manual.',
                'score'           => 1,
                'detected_type'   => 'SCANNED_OR_EMPTY_TEXT',
                'extracted_text'  => $extractedText
            ];
        }

        // 4. Analisis Klasifikasi Konten
        $analysis = self::classifyContent($extractedText);

        if ($analysis['type'] === 'SURAT_PENGANTAR') {
            return [
                'valid_technical' => true,
                'status_berkas'   => 'Valid Otomatis',
                'catatan'         => 'Terverifikasi otomatis: memuat indikator surat pengantar magang resmi.',
                'score'           => $analysis['score'],
                'detected_type'   => $analysis['type'],
                'extracted_text'  => substr($extractedText, 0, 500)
            ];
        } elseif ($analysis['type'] === 'SALAH_DOKUMEN') {
            return [
                'valid_technical' => true,
                'status_berkas'   => 'Tidak Valid',
                'catatan'         => 'Dokumen terdeteksi bukan Surat Pengantar (' . $analysis['detail'] . ').',
                'score'           => $analysis['score'],
                'detected_type'   => $analysis['type'],
                'extracted_text'  => substr($extractedText, 0, 500)
            ];
        } else {
            // AMBIGUOUS / PERLU MANUAL CHECK
            return [
                'valid_technical' => true,
                'status_berkas'   => 'Perlu Pemeriksaan Manual',
                'catatan'         => 'Indikator surat pengantar belum cukup tegas. Perlu pemeriksaan manual oleh Admin.',
                'score'           => $analysis['score'],
                'detected_type'   => $analysis['type'],
                'extracted_text'  => substr($extractedText, 0, 500)
            ];
        }
    }

    /**
     * Memvalidasi file proposal (Dokumen Opsional)
     * Hanya memeriksa keabsahan teknis file PDF tanpa memaksakan indikator surat pengantar.
     * 
     * @param string $filepath Path fisik file di server
     * @param string $original_name Nama asli file
     * @return array
     */
    public static function validateProposal($filepath, $original_name = '') {
        if (empty($filepath) || !file_exists($filepath)) {
            return [
                'valid_technical' => true,
                'status_proposal' => 'Tidak Diunggah',
                'catatan'         => 'Proposal tidak diunggah (dokumen opsional).'
            ];
        }

        $techCheck = self::validateTechnical($filepath, $original_name);
        if (!$techCheck['valid']) {
            return [
                'valid_technical' => false,
                'status_proposal' => 'Tidak Valid',
                'catatan'         => 'Berkas proposal tidak valid: ' . $techCheck['message']
            ];
        }

        return [
            'valid_technical' => true,
            'status_proposal' => 'Valid Otomatis',
            'catatan'         => 'Berkas proposal PDF valid secara teknis.'
        ];
    }

    /**
     * Validasi Teknis File (Eksistensi, Ukuran, Ekstensi, MIME type, Magic Bytes, Format PDF)
     */
    public static function validateTechnical($filepath, $original_name = '') {
        if (!file_exists($filepath)) {
            return ['valid' => false, 'message' => 'Berkas tidak ditemukan pada server.'];
        }

        $size = filesize($filepath);
        if ($size === false || $size <= 0) {
            return ['valid' => false, 'message' => 'Berkas berukuran kosong (0 byte).'];
        }

        if ($size > self::MAX_FILE_SIZE) {
            return ['valid' => false, 'message' => 'Ukuran berkas melebihi batas maksimal 5 MB.'];
        }

        if (!empty($original_name)) {
            $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                return ['valid' => false, 'message' => 'Ekstensi berkas harus berformat .pdf.'];
            }
        }

        // Cek MIME type dengan fileinfo
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $filepath);
            finfo_close($finfo);

            if ($mime !== 'application/pdf') {
                return ['valid' => false, 'message' => "Tipe konten berkas tidak valid: $mime (wajib application/pdf)."];
            }
        }

        // Cek Magic Bytes (%PDF-)
        $handle = fopen($filepath, 'rb');
        if (!$handle) {
            return ['valid' => false, 'message' => 'Berkas tidak dapat dibuka untuk diverifikasi.'];
        }
        $header = fread($handle, 1024);
        fclose($handle);

        if (strpos($header, '%PDF-') === false) {
            return ['valid' => false, 'message' => 'Signature berkas bukan dokumen PDF yang valid (%PDF- tidak ditemukan).'];
        }

        return ['valid' => true, 'message' => 'Valid teknis PDF.'];
    }

    /**
     * Mengekstrak teks dari berkas PDF murni PHP
     * Mendukung stream FlateDecode (zlib), operator Tj, TJ, Hex string, dan escaping.
     */
    public static function extractText($filepath) {
        $content = @file_get_contents($filepath);
        if ($content === false || strlen($content) === 0) {
            return '';
        }

        // Ambil seluruh stream dalam PDF
        preg_match_all('#stream[\r\n]+(.*?)[\r\n]+endstream#s', $content, $streamMatches);
        $extractedText = '';

        if (!empty($streamMatches[1])) {
            foreach ($streamMatches[1] as $stream) {
                // Coba decompress jika terkompresi FlateDecode
                $data = @gzuncompress($stream);
                if ($data === false) {
                    $data = @gzinflate($stream);
                }
                if ($data === false) {
                    $data = $stream; // uncompressed stream
                }

                // Ekstrak token teks dalam blok BT ... ET
                if (preg_match_all('#BT[\r\n\s]+(.*?)[\r\n\s]+ET#s', $data, $btBlocks)) {
                    foreach ($btBlocks[1] as $bt) {
                        $extractedText .= self::parseTextOperators($bt) . ' ';
                    }
                } else {
                    $extractedText .= self::parseTextOperators($data) . ' ';
                }
            }
        }

        // Normalisasi teks
        $normalized = preg_replace('/[^\x20-\x7E\x0A\x0D]/', ' ', $extractedText);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return trim($normalized);
    }

    /**
     * Memeriksa keberadaan stream gambar / XObject untuk deteksi dokumen hasil scan
     */
    public static function hasImageStreams($filepath) {
        $content = @file_get_contents($filepath);
        if ($content === false) {
            return false;
        }
        return (stripos($content, '/Subtype /Image') !== false || stripos($content, '/Subtype/Image') !== false || stripos($content, '/XObject') !== false);
    }

    /**
     * Parser operator teks PDF (Tj, TJ, Hex)
     */
    private static function parseTextOperators($block) {
        $text = '';

        // Match format: (text) Tj
        if (preg_match_all('#\((.*?)\)\s*Tj#s', $block, $tjMatches)) {
            foreach ($tjMatches[1] as $m) {
                $text .= self::decodePdfString($m) . ' ';
            }
        }

        // Match format: [(t1) -120 (t2)] TJ
        if (preg_match_all('#\[(.*?)\]\s*TJ#s', $block, $tjArrayMatches)) {
            foreach ($tjArrayMatches[1] as $arr) {
                if (preg_match_all('#\((.*?)\)#s', $arr, $sub)) {
                    foreach ($sub[1] as $s) {
                        $text .= self::decodePdfString($s);
                    }
                    $text .= ' ';
                }
            }
        }

        // Match format hex: <48656c6c6f> Tj
        if (preg_match_all('#<([0-9A-Fa-f\s]+)>\s*Tj#s', $block, $hexMatches)) {
            foreach ($hexMatches[1] as $hex) {
                $cleanHex = preg_replace('/\s+/', '', $hex);
                $decoded = @hex2bin($cleanHex);
                if ($decoded !== false) {
                    $text .= $decoded . ' ';
                }
            }
        }

        return $text;
    }

    /**
     * Decode karakter escape dalam string PDF
     */
    private static function decodePdfString($str) {
        $str = str_replace(['\\(', '\\)', '\\\\'], ['(', ')', '\\'], $str);
        // Octal escape \ddd
        $str = preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $str);
        return $str;
    }

    /**
     * Klasifikasi konten teks berdasarkan indikator surat pengantar magang vs materi lain
     */
    public static function classifyContent($text) {
        $textLower = strtolower($text);

        // 1. KATA KUNCI POSITIF SURAT PENGANTAR MAGANG
        $positiveKeywords = [
            'surat pengantar'            => 4,
            'pengantar magang'           => 4,
            'permohonan magang'          => 4,
            'permohonan pkl'             => 4,
            'praktik kerja lapangan'     => 3,
            'praktik kerja industri'     => 3,
            'izin magang'                => 3,
            'rekomendasi magang'         => 3,
            'pengajuan magang'           => 3,
            'balai teknologi komunikasi' => 3,
            'btkp'                       => 2,
            'dinas pendidikan'           => 2,
            'kepada yth'                 => 2,
            'dengan hormat'              => 2,
            'sekolah menengah kejuruan'  => 2,
            'universitas'                => 1,
            'fakultas'                   => 1,
            'nomor:'                     => 1,
            'lampiran:'                  => 1,
            'hal:'                       => 1
        ];

        // 2. KATA KUNCI NEGATIF (DOKUMEN TIDAK SESUAI)
        $negativeKeywords = [
            'bab 1 '                     => ['score' => 4, 'type' => 'Modul / Laporan Kuliah'],
            'bab 2 '                     => ['score' => 4, 'type' => 'Modul / Laporan Kuliah'],
            'bab 7 '                     => ['score' => 5, 'type' => 'Modul / Bab Praktikum'],
            'switching & stp'            => ['score' => 5, 'type' => 'Materi Jaringan Komputer'],
            'packet tracer'              => ['score' => 5, 'type' => 'Materi Praktikum'],
            'pertemuan 7 dari'           => ['score' => 5, 'type' => 'Modul Pertemuan Kuliah'],
            'sertifikat penghargaan'     => ['score' => 5, 'type' => 'Sertifikat Penghargaan'],
            'certificate of completion'  => ['score' => 5, 'type' => 'Sertifikat'],
            'diberikan kepada'           => ['score' => 4, 'type' => 'Sertifikat'],
            'laporan akhir magang'       => ['score' => 4, 'type' => 'Laporan Akhir'],
            'lembar pengesahan'          => ['score' => 3, 'type' => 'Lembar Pengesahan Laporan']
        ];

        // Hitung skor positif
        $posScore = 0;
        $matchedPos = [];
        foreach ($positiveKeywords as $kw => $weight) {
            if (strpos($textLower, $kw) !== false) {
                $posScore += $weight;
                $matchedPos[] = $kw;
            }
        }

        // Hitung skor negatif
        $negScore = 0;
        $negDetails = [];
        foreach ($negativeKeywords as $nkw => $meta) {
            if (strpos($textLower, $nkw) !== false) {
                $negScore += $meta['score'];
                $negDetails[] = $meta['type'];
            }
        }

        // Jika terdeteksi kata kunci negatif kuat (misal Bab kuliah, Sertifikat, dll)
        if ($negScore >= 4) {
            return [
                'type'   => 'SALAH_DOKUMEN',
                'detail' => implode(', ', array_unique($negDetails)),
                'score'  => $posScore - $negScore
            ];
        }

        // Ambang batas lolos otomatis
        if ($posScore >= 4) {
            return [
                'type'   => 'SURAT_PENGANTAR',
                'detail' => 'Memuat kata kunci resmi: ' . implode(', ', array_slice($matchedPos, 0, 3)),
                'score'  => $posScore
            ];
        }

        // Jika ada beberapa kata kunci tetapi belum memenuhi ambang batas pasti
        return [
            'type'   => 'PERLU_PEMERIKSAAN_MANUAL',
            'detail' => 'Kata kunci parsial: ' . (!empty($matchedPos) ? implode(', ', $matchedPos) : 'Teks umum'),
            'score'  => $posScore
        ];
    }
}
