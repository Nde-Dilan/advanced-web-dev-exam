// Global functions for event display and interaction

function displayEvents(events, containerId) {
    const container = document.getElementById(containerId);
    
    if (!events || events.length === 0) {
        container.innerHTML = '<div class="col-12"><div class="alert alert-info">No events found.</div></div>';
        return;
    }
    
    let html = '';
    events.forEach(event => {
        html += `
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="${event.image || 'assets/images/default-event.jpg'}" class="card-img-top" alt="${event.title}" style="height: 200px; object-fit: cover;">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${event.title}</h5>
                        <p class="card-text">${event.description.substring(0, 100)}...</p>
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>${event.formatted_date}
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>${event.formatted_time}
                                </small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>${event.venue}
                                </small>
                                <span class="badge bg-primary">${event.price_formatted}</span>
                            </div>
                            <button class="btn btn-primary w-100" onclick="viewEvent(${event.id})">
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function viewEvent(eventId) {
    // Redirect to event details page (to be implemented)
    window.location.href = `event-details.php?id=${eventId}`;
}

// Toast notifications
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1055';
    document.body.appendChild(container);
    return container;
}