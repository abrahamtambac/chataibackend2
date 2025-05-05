<script>
<?php if ($show_upload_modal): ?>
    document.addEventListener('DOMContentLoaded', function() {
        var uploadModal = new bootstrap.Modal(document.getElementById('uploadImageModal'), {
            backdrop: 'static',
            keyboard: false
        });
        uploadModal.show();
    });
<?php endif; ?>

document.addEventListener('DOMContentLoaded', function() {
    var viewModal = new bootstrap.Modal(document.getElementById('viewMeetingModal'));
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteMeetingModal'));

    // AJAX untuk pencarian pengguna
    $('#search_users').on('click', function() {
        var query = $('#search_query').val();
        if (query.length < 2) {
            alert('Masukkan setidaknya 2 karakter untuk pencarian.');
            return;
        }

        $('#loading').show();
        $('#search-results').empty();

        $.ajax({
            url: 'index.php?page=admin_dashboard&action=search_users',
            type: 'GET',
            data: { query: query },
            dataType: 'json',
            success: function(data) {
                $('#loading').hide();
                if (data.length > 0) {
                    var html = '<div id="search-results">';
                    data.forEach(function(user) {
                        html += `
                            <div class="user-result">
                                <input type="checkbox" name="invited_users[]" value="${user.id}" class="me-2">
                                <img src="${user.profile_image ? '../upload/image/' + user.profile_image : '../image/robot-ai.png'}" 
                                     alt="${user.name}" class="invited-user-img">
                                <span>${user.name} (${user.email})</span>
                            </div>
                        `;
                    });
                    html += '</div>';
                    $('#search-results').html(html);
                } else {
                    $('#search-results').html('<p class="text-muted">Tidak ada pengguna ditemukan.</p>');
                }
            },
            error: function(xhr, status, error) {
                $('#loading').hide();
                $('#search-results').html('<p class="text-danger">Terjadi kesalahan saat pencarian: ' + error + '</p>');
                console.log('Search AJAX Error: ' + xhr.responseText);
            }
        });
    });

    // Tampilkan detail meeting di modal
    $('.view-meeting').on('click', function() {
        var meetingId = $(this).data('meeting-id');
        $.ajax({
            url: 'index.php?page=admin_dashboard&action=get_meeting_details',
            type: 'GET',
            data: { meeting_id: meetingId },
            dataType: 'json',
            success: function(data) {
                if (data.error) {
                    $('#meetingDetails').html('<p class="text-danger">' + data.error + '</p>');
                } else {
                    var html = `
                        <p><strong>Judul:</strong> ${data.title}</p>
                        <p><strong>Tanggal:</strong> ${data.date}</p>
                        <p><strong>Waktu:</strong> ${data.time}</p>
                        <p><strong>Platform:</strong> ${data.platform}</p>
                        <p><strong>Dibuat oleh:</strong> ${data.creator}</p>
                        <p><strong>Peserta:</strong></p><ul>
                    `;
                    if (data.invited_users.length > 0) {
                        data.invited_users.forEach(function(user) {
                            html += `
                                <li>
                                    <img src="${user.profile_image ? '../upload/image/' + user.profile_image : '../image/robot-ai.png'}" 
                                         class="invited-user-img">
                                    ${user.name} (${user.email})
                                </li>
                            `;
                        });
                    } else {
                        html += '<li>Tidak ada peserta.</li>';
                    }
                    html += '</ul>';
                    $('#meetingDetails').html(html);
                    viewModal.show();
                }
            },
            error: function(xhr, status, error) {
                $('#meetingDetails').html('<p class="text-danger">Gagal memuat detail meeting: ' + error + '</p>');
                console.log('Details AJAX Error: ' + xhr.responseText);
                viewModal.show();
            }
        });
    });

    // Hapus meeting
    $('.delete-meeting').on('click', function() {
        var meetingId = $(this).data('meeting-id');
        $('#deleteMeetingId').val(meetingId);
        deleteModal.show();
    });

    // Validasi form sebelum submit
    $('#addMeetingForm').on('submit', function(e) {
        const invitedUsers = $('input[name="invited_users[]"]:checked').length;
        if (invitedUsers === 0) {
            e.preventDefault();
            alert('Harap pilih setidaknya satu pengguna untuk diundang.');
        }
    });
});
</script>
</body>
</html>