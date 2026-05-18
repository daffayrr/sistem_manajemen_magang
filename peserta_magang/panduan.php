<?php
session_start();

// 1. CEK LOGIN
if (!isset($_SESSION['status']) || $_SESSION['status'] != 'login' || $_SESSION['role_akses'] != 'Peserta') {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}

include 'layouts/header.php';
include 'layouts/sidebar.php';
include 'layouts/navbar.php';
?>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Panduan Pendaftaran</h4>
            <p class="text-muted small mb-0">Tata cara dan alur penerimaan magang di BTKP DIY.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold m-0 text-primary"><i class="fa-solid fa-list-ol me-2"></i> Langkah-Langkah Pendaftaran</h6>
                </div>
                <div class="card-body p-4">
                    
                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">1</div>
                        </div>
                        <div class="ms-3">
                            <h6 class="fw-bold text-dark">Registrasi & Login Akun</h6>
                            <p class="text-muted small mb-2">
                                Calon peserta wajib membuat akun terlebih dahulu melalui halaman Registrasi. Pastikan menggunakan email aktif dan nomor WhatsApp yang bisa dihubungi.
                            </p>
                            <div class="alert alert-light border small text-muted"><i class="fa-solid fa-check text-success me-2"></i>Status: Selesai</div>
                        </div>
                    </div>

                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">2</div>
                        </div>
                        <div class="ms-3">
                            <h6 class="fw-bold text-dark">Lengkapi Formulir Pendaftaran</h6>
                            <p class="text-muted small mb-2">
                                Masuk ke menu <b>Formulir Pendaftaran</b>. Isi data diri lengkap, data instansi (Sekolah/Kampus), dan data pembimbing.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">3</div>
                        </div>
                        <div class="ms-3">
                            <h6 class="fw-bold text-dark">Unggah Berkas Persyaratan</h6>
                            <p class="text-muted small mb-2">
                                Siapkan berkas dalam format <b>PDF</b> (Maksimal 2MB):
                            </p>
                            <ul class="small text-muted mb-0">
                                <li>Surat Pengantar dari Sekolah/Kampus (Wajib).</li>
                                <li>Proposal Magang (Opsional/Jika ada).</li>
                            </ul>
                        </div>
                    </div>

                    <div class="d-flex mb-4">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">4</div>
                        </div>
                        <div class="ms-3">
                            <h6 class="fw-bold text-dark">Tunggu Verifikasi (Sistem FIFO)</h6>
                            <p class="text-muted small mb-2">
                                Admin akan memverifikasi berkas berdasarkan urutan waktu masuk (<i>First In First Out</i>). 
                                Semakin cepat Anda mendaftar, semakin cepat berkas Anda diproses.
                            </p>
                            <span class="badge bg-warning text-dark">Status: Menunggu</span>
                        </div>
                    </div>

                    <div class="d-flex">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">5</div>
                        </div>
                        <div class="ms-3">
                            <h6 class="fw-bold text-dark">Pengumuman & Unduhan</h6>
                            <p class="text-muted small mb-0">
                                Cek secara berkala menu <b>Unduhan & Hasil</b>. Jika diterima, Anda dapat mengunduh Surat Balasan Resmi dari BTKP DIY di menu tersebut.
                            </p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-4">
            
            <div class="card border-0 shadow-sm rounded-3 mb-4 bg-primary text-white" style="background: linear-gradient(135deg, #0d2141 0%, #1a3a6c 100%);">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-2"><i class="fa-solid fa-circle-info me-2"></i> Info Penting</h5>
                    <p class="small opacity-75 mb-0">
                        Kuota magang terbatas setiap periodenya. Sistem akan otomatis menutup pendaftaran jika kuota penuh.
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold m-0 text-dark">Pertanyaan Umum (FAQ)</h6>
                </div>
                <div class="accordion accordion-flush" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Berapa lama proses verifikasi?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small text-muted">
                                Proses verifikasi memakan waktu 3-5 hari kerja, tergantung jumlah antrean pendaftar yang masuk.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Apa itu metode FIFO?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small text-muted">
                                <i>First In First Out</i> artinya pendaftar yang mengirim berkas lebih awal akan diprioritaskan untuk diperiksa dan diterima terlebih dahulu.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Bagaimana jika ditolak?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body small text-muted">
                                Jika ditolak (misal karena kuota penuh atau berkas salah), status Anda akan berubah menjadi "Ditolak". Anda dapat mencoba mendaftar lagi di periode berikutnya.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3 mt-4">
                <div class="card-body text-center p-4">
                    <i class="fa-solid fa-headset fa-3x text-muted mb-3"></i>
                    <h6 class="fw-bold">Butuh Bantuan?</h6>
                    <p class="small text-muted mb-3">Hubungi Sub Bagian Tata Usaha BTKP DIY jika mengalami kendala teknis.</p>
                    <a href="https://wa.me/6281234567890" target="_blank" class="btn btn-outline-success btn-sm rounded-pill px-4">
                        <i class="fa-brands fa-whatsapp me-1"></i> Hubungi Admin
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'layouts/footer.php'; ?>