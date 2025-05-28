<?php
session_start();
require_once '../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: ../auth/login.php');
    exit;
}

// Handle form submission
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO events (title, description, event_date, event_time, venue, location, 
                                  organizer, organizer_contact, price, available_tickets, total_tickets, image, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $_POST['title'], $_POST['description'], $_POST['event_date'], $_POST['event_time'],
                $_POST['venue'], $_POST['location'], $_POST['organizer'], $_POST['organizer_contact'],
                $_POST['price'], $_POST['available_tickets'], $_POST['available_tickets'], $_POST['image']
            ]);
            $success = "Event created successfully!";
            
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("
                UPDATE events SET title=?, description=?, event_date=?, event_time=?, venue=?, location=?,
                                organizer=?, organizer_contact=?, price=?, available_tickets=?, image=?, updated_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([
                $_POST['title'], $_POST['description'], $_POST['event_date'], $_POST['event_time'],
                $_POST['venue'], $_POST['location'], $_POST['organizer'], $_POST['organizer_contact'],
                $_POST['price'], $_POST['available_tickets'], $_POST['image'], $_POST['event_id']
            ]);
            $success = "Event updated successfully!";
            
        } elseif ($action === 'delete') {
            // Check if event has bookings
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM booking_items bi JOIN bookings b ON bi.booking_id = b.id WHERE bi.event_id = ? AND b.booking_status != 'cancelled'");
            $stmt->execute([$_POST['event_id']]);
            $bookingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($bookingCount > 0) {
                $error = "Cannot delete event with active bookings. Please cancel all bookings first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
                $stmt->execute([$_POST['event_id']]);
                $success = "Event deleted successfully!";
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get all events
try {
    $stmt = $pdo->query("
        SELECT e.*, 
               COALESCE(SUM(bi.quantity), 0) as tickets_sold,
               COALESCE(SUM(bi.subtotal), 0) as revenue
        FROM events e
        LEFT JOIN booking_items bi ON e.id = bi.event_id
        LEFT JOIN bookings b ON bi.booking_id = b.id AND b.booking_status != 'cancelled'
        GROUP BY e.id
        ORDER BY e.event_date DESC
    ");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = [];
    $error = "Error loading events: " . $e->getMessage();
}

require_once '../includes/header.php';
?>

<!-- Custom Admin Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-cogs"></i> EventBook Admin
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="events.php">
                        <i class="fas fa-calendar"></i> Events
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="bookings.php">
                        <i class="fas fa-ticket-alt"></i> Bookings
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="reports.php">
                        <i class="fas fa-chart-line"></i> Reports
                    </a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../index.php">
                            <i class="fas fa-home"></i> View Site
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-calendar"></i> Event Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal" onclick="openEventModal()">
                    <i class="fas fa-plus"></i> Add New Event
                </button>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Events Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Event</th>
                                    <th>Date & Time</th>
                                    <th>Venue</th>
                                    <th>Price</th>
                                    <th>Tickets</th>
                                    <th>Revenue</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo htmlspecialchars('../'.$event['image'] ?? '../assets/images/default-event.jpg'); ?>" 
                                                     class="me-3 rounded" style="width: 50px; height: 50px; object-fit: cover;" alt="Event">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($event['title']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars(substr($event['description'], 0, 50)); ?>...</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo date('M j, Y', strtotime($event['event_date'])); ?><br>
                                            <small class="text-muted"><?php echo date('g:i A', strtotime($event['event_time'])); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($event['venue']); ?><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($event['location']); ?></small>
                                        </td>
                                        <td>XAF <?php echo number_format($event['price'], 2); ?></td>
                                        <td>
                                            <?php echo $event['available_tickets']; ?> / <?php echo $event['total_tickets']; ?><br>
                                            <small class="text-muted"><?php echo $event['tickets_sold']; ?> sold</small>
                                        </td>
                                        <td class="text-success">$<?php echo number_format($event['revenue'], 2); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="editEvent(<?php echo htmlspecialchars(json_encode($event)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteEvent(<?php echo $event['id']; ?>, '<?php echo htmlspecialchars($event['title']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Event Modal -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eventModalTitle">Add New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="eventForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" id="eventAction" value="create">
                    <input type="hidden" name="event_id" id="eventId">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="title" class="form-label">Event Title *</label>
                                <input type="text" class="form-control" name="title" id="title" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="organizer" class="form-label">Organizer *</label>
                                <input type="text" class="form-control" name="organizer" id="organizer" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" name="description" id="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_date" class="form-label">Event Date *</label>
                                <input type="date" class="form-control" name="event_date" id="event_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="event_time" class="form-label">Event Time *</label>
                                <input type="time" class="form-control" name="event_time" id="event_time" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="venue" class="form-label">Venue *</label>
                                <input type="text" class="form-control" name="venue" id="venue" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="location" class="form-label">Location/City *</label>
                                <input type="text" class="form-control" name="location" id="location" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="price" class="form-label">Ticket Price *</label>
                                <input type="number" class="form-control" name="price" id="price" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="available_tickets" class="form-label">Available Tickets *</label>
                                <input type="number" class="form-control" name="available_tickets" id="available_tickets" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="organizer_contact" class="form-label">Contact</label>
                                <input type="text" class="form-control" name="organizer_contact" id="organizer_contact">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Image URL</label>
                        <input type="url" class="form-control" name="image" id="image">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEventModal(action = 'create') {
    document.getElementById('eventModalTitle').textContent = action === 'create' ? 'Add New Event' : 'Edit Event';
    document.getElementById('eventAction').value = action;
    document.getElementById('eventForm').reset();
    if (action === 'create') {
        document.getElementById('eventId').value = '';
    }
}

function editEvent(event) {
    openEventModal('update');
    
    // Populate form fields
    document.getElementById('eventId').value = event.id;
    document.getElementById('title').value = event.title;
    document.getElementById('description').value = event.description;
    document.getElementById('event_date').value = event.event_date;
    document.getElementById('event_time').value = event.event_time;
    document.getElementById('venue').value = event.venue;
    document.getElementById('location').value = event.location;
    document.getElementById('organizer').value = event.organizer;
    document.getElementById('organizer_contact').value = event.organizer_contact || '';
    document.getElementById('price').value = event.price;
    document.getElementById('available_tickets').value = event.available_tickets;
    document.getElementById('image').value = event.image || '';
    
    const modal = new bootstrap.Modal(document.getElementById('eventModal'));
    modal.show();
}

function deleteEvent(eventId, eventTitle) {
    if (confirm(`Are you sure you want to delete "${eventTitle}"? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="event_id" value="${eventId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
