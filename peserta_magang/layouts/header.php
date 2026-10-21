<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Proteksi Autentikasi Peserta
if (!isset($_SESSION['status']) || $_SESSION['status'] != 'login' || $_SESSION['role_akses'] != 'Peserta') {
    header("Location: ../index.php?pesan=belum_login");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Peserta - Magang BTKP DIY</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --sidebar-width: 260px;
            --header-height: 70px;
            --footer-height: 60px;
            --primary-navy: #0d2141; 
            --active-blue: #e3f2fd;
            --bg-body: #f4f6f9;
            --bg-card: #ffffff;
            --bg-sidebar: #ffffff;
            --text-main: #343a40;
            --text-muted: #6c757d;
            --border-color: #e9ecef;
            --table-hover: #f8f9fa;
            --shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        body { font-family: 'Inter', sans-serif; background-color: var(--bg-body); color: var(--text-main); font-size: 0.9rem; overflow-x: hidden; }
        a { text-decoration: none; }

        /* Layout */
        .sidebar { width: var(--sidebar-width); height: 100vh; position: fixed; top: 0; left: 0; background-color: var(--bg-sidebar); border-right: 1px solid var(--border-color); z-index: 1040; padding: 20px 15px; display: flex; flex-direction: column; overflow-y: auto; transition: transform 0.3s ease; }
        .top-header { position: fixed; top: 0; left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); height: var(--header-height); background-color: var(--bg-card); border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; z-index: 1030; transition: left 0.3s, width 0.3s; }
        .main-content { margin-top: var(--header-height); margin-left: var(--sidebar-width); padding: 30px; min-height: calc(100vh - var(--header-height)); padding-bottom: calc(var(--footer-height) + 30px); transition: margin-left 0.3s; }
        .app-footer { position: fixed; bottom: 0; left: var(--sidebar-width); width: calc(100% - var(--sidebar-width)); height: var(--footer-height); background-color: var(--bg-card); border-top: 1px solid var(--border-color); display: flex; align-items: center; justify-content: center; z-index: 1030; color: var(--text-muted); font-size: 0.85rem; transition: left 0.3s, width 0.3s; }

        /* Components */
        .card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 10px; box-shadow: var(--shadow); }
        .nav-link { color: var(--text-muted); font-weight: 500; padding: 12px 15px; margin-bottom: 5px; border-radius: 8px; transition: all 0.2s; display: flex; align-items: center; }
        
        /* Ikon Menu Rapi */
        .nav-link i { width: 30px; height: 20px; line-height: 20px; text-align: center; margin-right: 10px; flex-shrink: 0; font-size: 1.1rem; }
        
        .nav-link:hover { background-color: var(--table-hover); color: var(--primary-navy); }
        .nav-link:hover i { transform: translateX(2px); transition: transform 0.2s; }
        .nav-link.active { background-color: var(--primary-navy); color: white !important; box-shadow: 0 4px 6px rgba(13, 33, 65, 0.2); }

        /* Mobile */
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .top-header { left: 0; width: 100%; }
            .app-footer { left: 0; width: 100%; }
            .main-content { margin-left: 0; }
            .sidebar.show { transform: translateX(0); box-shadow: 0 0 15px rgba(0,0,0,0.5); }
        }
    </style>
</head>
<body>
