<footer class="app-footer">
    <div>
        &copy; 2025 <strong>Balai Teknologi Komunikasi Pendidikan DIY</strong>. 
        <span class="d-none d-sm-inline"> - Sistem Penerimaan Magang v1.0</span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle Sidebar Mobile
    window.toggleSidebar = function() {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar) sidebar.classList.toggle('show');
    };

    // Jam Realtime (Server Time Sync)
    document.addEventListener('DOMContentLoaded', function() {
        // Ambil waktu dari PHP yang sudah diset ke Asia/Jakarta
        // Format YYYY/MM/DD HH:mm:ss penting agar bisa diparsing JS
        var serverTime = new Date("<?php echo date('Y/m/d H:i:s'); ?>").getTime();

        setInterval(function() {
            serverTime += 1000; // Tambah 1 detik
            var date = new Date(serverTime);
            
            var h = date.getHours().toString().padStart(2, '0');
            var m = date.getMinutes().toString().padStart(2, '0');
            var s = date.getSeconds().toString().padStart(2, '0');
            
            var clockElement = document.getElementById('live-clock');
            if(clockElement) {
                clockElement.innerHTML = h + ":" + m + ":" + s + " WIB";
            }
        }, 1000);
    });
</script>

</body>
</html>