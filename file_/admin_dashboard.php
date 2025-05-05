<?php

include_once './controllers/AdminController.php';
include_once './config/db.php';

$adminController = new AdminController($conn);
$adminController->checkAdminAccess();

// Ambil data profil
$userProfile = $adminController->getUserProfile();
$profile_image = $userProfile['profile_image'];
$show_upload_modal = empty($profile_image);

// Proses upload gambar
$uploadResult = $adminController->uploadProfileImage();
if ($uploadResult['profile_image']) {
    $profile_image = $uploadResult['profile_image'];
    $show_upload_modal = false;
}
$upload_error = $uploadResult['upload_error'];

// Ambil data meeting
$meetings = $adminController->getMeetings();
$invitedMeetings = $adminController->getInvitedMeetings();

// Proses tambah meeting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_meeting'])) {
    $date = $_POST['meeting_date'];
    $time = $_POST['meeting_time'];
    $title = $_POST['meeting_title'];
    $platform = $_POST['meeting_platform'];
    $invited_users = isset($_POST['invited_users']) ? $_POST['invited_users'] : [];
    
    if (empty($invited_users)) {
        $meeting_error = "Harap pilih setidaknya satu pengguna untuk diundang.";
    } elseif ($adminController->addMeeting($date, $time, $title, $platform, $invited_users)) {
        header("Location: index.php?page=admin_dashboard");
        exit();
    } else {
        $meeting_error = "Gagal menambah meeting.";
    }
}

// Proses hapus meeting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_meeting'])) {
    $meeting_id = $_POST['meeting_id'];
    if ($adminController->deleteMeeting($meeting_id)) {
        header("Location: index.php?page=admin_dashboard");
        exit();
    } else {
        $delete_error = "Gagal menghapus meeting.";
    }
}

// AJAX handler untuk pencarian pengguna
if (isset($_GET['action']) && $_GET['action'] === 'search_users' && isset($_GET['query'])) {
    ob_clean();
    $search_query = $_GET['query'];
    $search_results = $adminController->searchUsers($search_query);
    header('Content-Type: application/json');
    echo json_encode($search_results);
    exit();
}

// AJAX handler untuk detail meeting
if (isset($_GET['action']) && $_GET['action'] === 'get_meeting_details' && isset($_GET['meeting_id'])) {
    ob_clean();
    $meeting_id = $_GET['meeting_id'];
    $details = $adminController->getMeetingDetails($meeting_id);
    header('Content-Type: application/json');
    if ($details) {
        $response = [
            'title' => $details['title'],
            'date' => date('D, d M', strtotime($details['date'])),
            'time' => date('h:i A', strtotime($details['time'])),
            'platform' => ucfirst($details['platform']),
            'invited_users' => $details['invited_users'],
            'creator' => $details['creator']
        ];
        echo json_encode($response);
    } else {
        echo json_encode(['error' => 'Meeting tidak ditemukan']);
    }
    exit();
}
?>

<?php include '_partials/_admin_head.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <br/>
            <h2 class="text-dark fw-bolder">Selamat datang, <?php echo htmlspecialchars($userProfile['name']); ?></h2>
            <p class="text-muted">Ikhtisar dashboard pribadi Anda</p>
        </div>
        <div>
            <a href="index.php?page=logout" class="ms-2 text-muted"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </div>

    <div class="row">
        <!-- Profile Card -->
        <div class="col-md-3">
            <div class="card-widget border">
                <div class="profile-card">
                    <img src="<?php echo !empty($profile_image) ? '../upload/image/' . $profile_image : '../image/robot-ai.png'; ?>" 
                         alt="Profil Admin" class="profile-img">
                    <div>
                        <h5 class="mb-0"><?php echo htmlspecialchars($userProfile['name']); ?></h5>
                        <p class="text-muted mb-0">Manajer Admin</p>
                        <p class="text-muted small">Email: <?php echo htmlspecialchars($userProfile['email']); ?></p>
                    </div>
                </div>
                <div class="d-flex justify-content-around mt-3 text-muted">
                    <span><i class="bi bi-people"></i> 11</span>
                    <span><i class="bi bi-clock"></i> 56</span>
                    <span><i class="bi bi-trophy"></i> 12</span>
                </div>
            </div>
        </div>

        <!-- My Meetings -->
        <div class="col-md-5">
            <div class="card-widget border">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5>Meeting Saya</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMeetingModal">
                        <i class="bi bi-plus"></i> Tambah Meeting
                    </button>
                </div>
                <?php foreach ($meetings as $meeting): ?>
                    <div class="meeting-item">
                        <span class="text-muted"><?php echo date('D, d M', strtotime($meeting['date'])); ?></span>
                        <p>
                            <?php echo htmlspecialchars($meeting['title']); ?> 
                            <span class="badge bg-<?php echo $meeting['platform'] === 'zoom' ? 'primary' : 'success'; ?>">
                                <?php echo date('h:i A', strtotime($meeting['time'])); ?>
                            </span>
                            <i class="bi bi-<?php echo $meeting['platform'] === 'zoom' ? 'zoom-in' : 'google'; ?> ms-2"></i>
                        </p>
                        <div>
                            <small class="text-muted">Peserta:</small>
                            <?php
                            $details = $adminController->getMeetingDetails($meeting['id']);
                            if (!empty($details['invited_users'])) {
                                foreach ($details['invited_users'] as $user): ?>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($user['profile_image']) ? '../upload/image/' . $user['profile_image'] : '../image/robot-ai.png'; ?>" 
                                             alt="<?php echo htmlspecialchars($user['name']); ?>" class="invited-user-img">
                                        <span><?php echo htmlspecialchars($user['name']); ?></span>
                                    </div>
                                <?php endforeach;
                            } else {
                                echo '<p class="text-muted">Tidak ada peserta.</p>';
                            }
                            ?>
                        </div>
                        <div class="mt-2">
                            <button class="btn btn-sm btn-info view-meeting" data-meeting-id="<?php echo $meeting['id']; ?>">Lihat</button>
                            <button class="btn btn-sm btn-danger delete-meeting" data-meeting-id="<?php echo $meeting['id']; ?>">Hapus</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <a href="#" class="text-primary mt-2 d-block">Lihat semua meeting <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>

        <!-- Invited Meetings -->
        <div class="col-md-4">
            <div class="card-widget border">
                <h5>Undangan Meeting</h5>
                <?php foreach ($invitedMeetings as $meeting): ?>
                    <div class="meeting-item">
                        <span class="text-muted"><?php echo date('D, d M', strtotime($meeting['date'])); ?></span>
                        <p>
                            <?php echo htmlspecialchars($meeting['title']); ?> 
                            <span class="badge bg-<?php echo $meeting['platform'] === 'zoom' ? 'primary' : 'success'; ?>">
                                <?php echo date('h:i A', strtotime($meeting['time'])); ?>
                            </span>
                            <i class="bi bi-<?php echo $meeting['platform'] === 'zoom' ? 'zoom-in' : 'google'; ?> ms-2"></i>
                            <br>
                            <small class="text-muted">Dibuat oleh: <?php echo htmlspecialchars($meeting['creator']); ?></small>
                        </p>
                        <button class="btn btn-sm btn-info view-meeting" data-meeting-id="<?php echo $meeting['id']; ?>">Lihat</button>
                    </div>
                <?php endforeach; ?>
                <a href="#" class="text-primary mt-2 d-block">Lihat semua undangan meeting <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>

<?php 
include '_partials/_admin_modals.php'; 
include '_partials/_admin_scripts.php'; 
?>