import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Helper function to get role badge HTML
window.getRoleBadge = function(roleKey) {
    if (roleKey === 'super_admin') {
        return '<span class="badge bg-danger">SUPER ADMIN</span>';
    } else if (roleKey === 'office_admin' || roleKey === 'admin') {
        return '<span class="badge bg-primary">ADMIN</span>';
    } else if (roleKey.includes('approver')) {
        return '<span class="badge bg-warning text-dark">APPROVER</span>';
    } else {
        return '<span class="badge bg-info text-dark">EMPLOYEE</span>';
    }
};

// Global function to open user view modal
window.openViewUserModal = function(employeeId) {
    const modalElement = document.getElementById('viewUserModal');
    const contentDiv = document.getElementById('viewUserContent');
    const fullDetailsLink = document.getElementById('viewUserFullDetails');

    // Check if modal exists
    if (!modalElement) {
        // If modal doesn't exist, redirect to user show page
        window.location.href = `/super/users/${employeeId}`;
        return;
    }

    // Get or create modal instance
    let modalInstance = bootstrap.Modal.getInstance(modalElement);
    if (!modalInstance) {
        modalInstance = new bootstrap.Modal(modalElement);
    }

    // Show loading state
    contentDiv.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

    // Set full details link
    fullDetailsLink.href = `/super/users/${employeeId}`;

    // Fetch user data
    fetch(`/super/users/${employeeId}/modal-data`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => {
        if (!response.ok) {
            if (response.status === 404) {
                throw new Error('User not found. This user may have been deleted.');
            }
            throw new Error('Error loading user data');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Use the template
            const template = document.getElementById('userDetailsTemplate');
            if (!template) {
                contentDiv.innerHTML = '<div class="alert alert-danger">Template not found</div>';
                modalInstance.show();
                return;
            }

            const clone = template.content.cloneNode(true);

            // Fill in account information
            clone.querySelector('.user-first-name').textContent = data.user.first_name;
            clone.querySelector('.user-middle-name').textContent = data.user.middle_name || '-';
            clone.querySelector('.user-last-name').textContent = data.user.last_name;
            clone.querySelector('.user-full-name').textContent = data.user.name;
            clone.querySelector('.user-email').textContent = data.user.email;

            // Status badge
            const statusBadge = data.status === 'active'
                ? '<span class="badge bg-success">Active</span>'
                : `<span class="badge bg-secondary">${data.status.charAt(0).toUpperCase() + data.status.slice(1)}</span>`;
            clone.querySelector('.user-status').innerHTML = statusBadge;

            // Fill in employment information
            clone.querySelector('.user-office').textContent = data.office || 'No Office';
            clone.querySelector('.user-division').textContent = data.division || 'No Division';
            clone.querySelector('.user-position').textContent = data.position_title;
            clone.querySelector('.user-salary-grade').textContent = data.salary_grade || '-';
            clone.querySelector('.user-sex').textContent = data.sex ? (data.sex === 'M' ? 'Male' : 'Female') : '-';

            // Fill in roles
            const rolesContainer = clone.querySelector('.user-roles-container');
            if (data.roles && data.roles.length > 0) {
                data.roles.forEach(role => {
                    const col = document.createElement('div');
                    col.className = 'col-md-4 mb-2';
                    col.innerHTML = `
                        <div class="card border-0 bg-light h-100">
                            <div class="card-body py-2 px-2">
                                <div class="fw-bold mb-1 small">${role.name}</div>
                                <div class="small text-muted mb-1">${role.key}</div>
                                ${window.getRoleBadge(role.key)}
                            </div>
                        </div>
                    `;
                    rolesContainer.appendChild(col);
                });
            } else {
                rolesContainer.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0 small">No roles assigned to this user.</div></div>';
            }

            // Replace loading content with actual content
            contentDiv.innerHTML = '';
            contentDiv.appendChild(clone);

            modalInstance.show();
        } else {
            contentDiv.innerHTML = `<div class="alert alert-danger">${data.message || 'Error loading user data'}</div>`;
            modalInstance.show();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        contentDiv.innerHTML = `<div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            ${error.message || 'Error loading user data'}
            <div class="mt-2">
                <button class="btn btn-sm btn-outline-secondary" onclick="this.closest('.modal-content').querySelector('.btn-close').click()">Close</button>
            </div>
        </div>`;
        modalInstance.show();
    });
};
