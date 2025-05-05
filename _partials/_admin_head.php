<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starvee - Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <style>
        .dashboard-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .card-widget { background: white; border-radius: 15px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); padding: 20px; margin-bottom: 20px; transition: transform 0.2s; }
        .card-widget:hover { transform: translateY(-2px); }
        .profile-card, .user-result { display: flex; align-items: center; gap: 15px; }
        .profile-img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid #0d6efd; }
        .meeting-item { background: #f5f7fa; border-radius: 10px; padding: 10px; margin-bottom: 10px; transition: background 0.2s; }
        .meeting-item:hover { background: #e0e7f0; }
        .modal-content { border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .modal-header { background: linear-gradient(135deg, #0d6efd, #0a58ca); border-radius: 15px 15px 0 0; }
        .form-control:focus { box-shadow: 0 0 10px rgba(13,110,253,0.3); }
        .invited-user-img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 2px solid #0d6efd; margin-right: 10px; }
        #search-results { max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body></body>