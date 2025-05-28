<?php
session_start();
require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <h1 class="text-center mb-4">Welcome to EventBook</h1>
            <p class="text-center lead">Discover and book amazing events near you</p>
        </div>
    </div>
    
    <!-- Featured Events Section -->
    <div class="row mt-5">
        <div class="col-md-12">
            <h2>Featured Events</h2>
            <div id="featured-events" class="row">
                <!-- Events will be loaded here via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
// Load featured events on page load
document.addEventListener('DOMContentLoaded', function() {
    loadFeaturedEvents();
});

function loadFeaturedEvents() {
    fetch('api/get_events.php?featured=true')
        .then(response => response.json())
        .then(data => {
            displayEvents(data, 'featured-events');
        })
        .catch(error => console.error('Error:', error));
}
</script>

<?php require_once 'includes/footer.php'; ?>