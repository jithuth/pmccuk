<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white border-0">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-circle me-2"></i> Member Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="viewModalBody">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading profile data...</p>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function editMember(id) {
    const body = document.getElementById('viewModalBody');
    body.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Fetching data...</p></div>';
    
    $('#viewModal').modal('show');

    fetch(`/admin/members/${id}/details`)
        .then(res => res.json())
        .then(data => {
            // Unscramble any remaining encryption if it leaked through
            const fullName = data.full_name || 'N/A';
            const email = data.email || 'N/A';
            const mobile = data.mobile_number || 'N/A';
            
            let childrenHtml = '';
            if(data.children && data.children.length > 0) {
                childrenHtml = '<ul class="list-group list-group-flush border rounded mt-2">';
                data.children.forEach(c => {
                    childrenHtml += `<li class="list-group-content p-2 small border-bottom">
                        <strong>${c.child_name}</strong> (${c.sex}) - ${c.dob || 'No DOB'}
                    </li>`;
                });
                childrenHtml += '</ul>';
            } else {
                childrenHtml = '<p class="text-muted small">No children listed.</p>';
            }

            body.innerHTML = `
                <div class="p-4">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-3 text-center">
                            <img src="${data.photo_url}" class="img-fluid rounded-circle border shadow-sm mb-2" style="width: 120px; height: 120px; object-fit: cover;">
                        </div>
                        <div class="col-md-9">
                            <h3 class="fw-bold mb-1 text-primary">${fullName}</h3>
                            <p class="text-muted mb-2"><i class="fas fa-id-badge me-2"></i> ID: <strong>${data.membership_id_assigned || 'PENDING'}</strong></p>
                            <span class="badge bg-${data.status === 'active' ? 'success' : 'warning'} rounded-pill px-3">${data.status.toUpperCase()}</span>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded">
                                <label class="text-uppercase small fw-bold text-muted d-block">Contact Info</label>
                                <div class="mb-2"><strong>Email:</strong> ${email}</div>
                                <div class="mb-2"><strong>Mobile:</strong> ${mobile}</div>
                                <div class="mb-0"><strong>Area:</strong> ${data.residing_area || 'N/A'}</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light p-3 rounded h-100">
                                <label class="text-uppercase small fw-bold text-muted d-block">Membership Details</label>
                                <div class="mb-2"><strong>Type:</strong> ${data.membership_type}</div>
                                <div class="mb-2"><strong>Expiry:</strong> ${data.expiry_date || 'N/A'}</div>
                                <div class="mb-0"><strong>Joined:</strong> ${new Date(data.created_at).toLocaleDateString()}</div>
                            </div>
                        </div>
                        <div class="col-12 mt-3">
                            <label class="text-uppercase small fw-bold text-muted">Family & Children</label>
                            ${childrenHtml}
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(err => {
            body.innerHTML = '<div class="p-5 text-center text-danger"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>Failed to load member details.</div>';
        });
}
</script>
