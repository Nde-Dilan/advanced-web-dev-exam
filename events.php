<?php
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <!-- Search and Filter Section -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4>Find Events</h4>
                    <form id="search-form">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="text" class="form-control" id="search-name" placeholder="Search by event name...">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="search-location" placeholder="Location">
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" id="search-date">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Events Grid -->
    <div class="row">
        <div class="col-md-12">
            <div id="events-container" class="row">
                <!-- Events will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="loading" class="text-center d-none">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadEvents();
    
    // Search form submission
    document.getElementById('search-form').addEventListener('submit', function(e) {
        e.preventDefault();
        searchEvents();
    });
});

function loadEvents() {
    showLoading();
    fetch('api/get_events.php')
        .then(response => response.json())
        .then(data => {
            displayEvents(data, 'events-container');
            hideLoading();
        })
        .catch(error => {
            console.error('Error:', error);
            hideLoading();
        });
}

function searchEvents() {
    showLoading();
    const searchParams = new URLSearchParams({
        name: document.getElementById('search-name').value,
        location: document.getElementById('search-location').value,
        date: document.getElementById('search-date').value
    });
    
    fetch('api/search_events.php?' + searchParams)
        .then(response => response.json())
        .then(data => {
            displayEvents(data, 'events-container');
            hideLoading();
        })
        .catch(error => {
            console.error('Error:', error);
            hideLoading();
        });
}

function showLoading() {
    document.getElementById('loading').classList.remove('d-none');
}

function hideLoading() {
    document.getElementById('loading').classList.add('d-none');
}
</script>

<?php require_once 'includes/footer.php'; ?>