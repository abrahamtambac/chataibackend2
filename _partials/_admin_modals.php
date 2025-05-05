<!-- Upload Image Modal -->
<div class="modal fade" id="uploadImageModal" tabindex="-1" aria-labelledby="uploadImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="uploadImageModalLabel">Unggah Gambar Profil</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if ($upload_error): ?>
                    <div class="alert alert-danger"><?php echo $upload_error; ?></div>
                <?php endif; ?>
                <p>Silakan unggah gambar profil Anda untuk melanjutkan.</p>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="profile_image" class="form-label">Pilih gambar:</label>
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Unggah</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Meeting Modal -->
<div class="modal fade" id="addMeetingModal" tabindex="-1" aria-labelledby="addMeetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title" id="addMeetingModalLabel">Tambah Meeting Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (isset($meeting_error)): ?>
                    <div class="alert alert-danger"><?php echo $meeting_error; ?></div>
                <?php endif; ?>
                <form method="POST" id="addMeetingForm">
                    <div class="mb-3">
                        <label for="meeting_title" class="form-label">Judul</label>
                        <input type="text" class="form-control" id="meeting_title" name="meeting_title" required>
                    </div>
                    <div class="mb-3">
                        <label for="meeting_date" class="form-label">Tanggal</label>
                        <input type="date" class="form-control" id="meeting_date" name="meeting_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="meeting_time" class="form-label">Waktu</label>
                        <input type="time" class="form-control" id="meeting_time" name="meeting_time" required>
                    </div>
                    <div class="mb-3">
                        <label for="meeting_platform" class="form-label">Platform</label>
                        <select class="form-control" id="meeting_platform" name="meeting_platform" required>
                            <option value="zoom">Zoom</option>
                            <option value="google">Google Meet</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Undang Pengguna</label>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control" id="search_query" placeholder="Masukkan nama atau email" required>
                            <button class="btn btn-primary" type="button" id="search_users">Cari</button>
                        </div>
                        <div id="loading" class="text-center" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Memuat...</span>
                            </div>
                        </div>
                        <div id="search-results"></div>
                        <small class="text-muted">Centang pengguna yang ingin diundang</small>
                    </div>
                    <button type="submit" name="add_meeting" class="btn btn-primary w-100">Tambah Meeting</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Meeting Modal -->
<div class="modal fade" id="viewMeetingModal" tabindex="-1" aria-labelledby="viewMeetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header text-white">
                <h5 class="modal-title" id="viewMeetingModalLabel">Detail Meeting</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="meetingDetails"></div>
        </div>
    </div>
</div>

<!-- Delete Meeting Modal -->
<div class="modal fade" id="deleteMeetingModal" tabindex="-1" aria-labelledby="deleteMeetingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteMeetingModalLabel">Hapus Meeting</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus meeting ini?</p>
                <form method="POST" id="deleteMeetingForm">
                    <input type="hidden" name="meeting_id" id="deleteMeetingId">
                    <button type="submit" name="delete_meeting" class="btn btn-danger">Ya, Hapus</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                </form>
            </div>
        </div>
    </div>
</div>