<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'your_username';
$db_pass = 'your_password';
$db_name = 'journal_app';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) !== TRUE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($db_name);

// Create journal_entries table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS journal_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    mood VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) !== TRUE) {
    die("Error creating table: " . $conn->error);
}

// API endpoints
$request_method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

switch ($request_method) {
    case 'GET':
        if ($endpoint === 'entries') {
            getEntries();
        } elseif ($endpoint === 'entry' && isset($_GET['id'])) {
            getEntry($_GET['id']);
        }
        break;
    case 'POST':
        if ($endpoint === 'entry') {
            createEntry();
        }
        break;
    case 'PUT':
        if ($endpoint === 'entry' && isset($_GET['id'])) {
            updateEntry($_GET['id']);
        }
        break;
    case 'DELETE':
        if ($endpoint === 'entry' && isset($_GET['id'])) {
            deleteEntry($_GET['id']);
        }
        break;
}

// Function to get all entries
function getEntries() {
    global $conn;
    
    $sql = "SELECT * FROM journal_entries ORDER BY created_at DESC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $entries = array();
        while ($row = $result->fetch_assoc()) {
            $entries[] = $row;
        }
        echo json_encode($entries);
    } else {
        echo json_encode([]);
    }
}

// Function to get a specific entry
function getEntry($id) {
    global $conn;
    
    $id = $conn->real_escape_string($id);
    $sql = "SELECT * FROM journal_entries WHERE id = $id";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo json_encode($result->fetch_assoc());
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Entry not found']);
    }
}

// Function to create a new entry
function createEntry() {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $title = $conn->real_escape_string($data['title']);
    $content = $conn->real_escape_string($data['content']);
    $mood = isset($data['mood']) ? $conn->real_escape_string($data['mood']) : null;
    
    $sql = "INSERT INTO journal_entries (title, content, mood) VALUES ('$title', '$content', '$mood')";
    
    if ($conn->query($sql) === TRUE) {
        $id = $conn->insert_id;
        http_response_code(201);
        echo json_encode(['id' => $id, 'message' => 'Entry created successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $conn->error]);
    }
}

// Function to update an entry
function updateEntry($id) {
    global $conn;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $id = $conn->real_escape_string($id);
    $title = $conn->real_escape_string($data['title']);
    $content = $conn->real_escape_string($data['content']);
    $mood = isset($data['mood']) ? $conn->real_escape_string($data['mood']) : null;
    
    $sql = "UPDATE journal_entries SET title = '$title', content = '$content', mood = '$mood' WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['message' => 'Entry updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $conn->error]);
    }
}

// Function to delete an entry
function deleteEntry($id) {
    global $conn;
    
    $id = $conn->real_escape_string($id);
    $sql = "DELETE FROM journal_entries WHERE id = $id";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode(['message' => 'Entry deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $conn->error]);
    }
}

// If this script is not being accessed as an API endpoint, display the HTML
if (empty($endpoint)) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Journal</title>
    <style>
        :root {
            --primary: #6a5acd;
            --primary-light: #8a7ad8;
            --secondary: #4a4a8a;
            --accent: #9370db;
            --background: #f8f8ff;
            --text: #333344;
            --white: #ffffff;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--background);
            color: var(--text);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        .background-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }
        
        .bubble {
            position: absolute;
            border-radius: 50%;
            background: rgba(106, 90, 205, 0.1);
            animation: float 15s infinite ease-in-out;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.8;
            }
            50% {
                transform: translateY(-100px) rotate(180deg);
                opacity: 0.4;
            }
            100% {
                transform: translateY(0) rotate(360deg);
                opacity: 0.8;
            }
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            text-align: center;
            padding: 30px 0;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: var(--white);
            border-radius: 0 0 20px 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .date-display {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        
        .journal-container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .entries-section {
            flex: 1;
            min-width: 300px;
        }
        
        .editor-section {
            flex: 2;
            min-width: 400px;
        }
        
        .card {
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent);
        }
        
        .entry-list {
            max-height: 500px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .entry-item {
            padding: 15px;
            margin-bottom: 15px;
            background-color: rgba(106, 90, 205, 0.05);
            border-radius: 10px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            border-left: 3px solid var(--primary);
        }
        
        .entry-item:hover {
            background-color: rgba(106, 90, 205, 0.1);
        }
        
        .entry-item.active {
            background-color: rgba(106, 90, 205, 0.2);
            border-left: 3px solid var(--accent);
        }
        
        .entry-title {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .entry-date {
            font-size: 0.8rem;
            color: #666;
        }
        
        .entry-preview {
            font-size: 0.9rem;
            color: #555;
            margin-top: 5px;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .editor-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        label {
            font-weight: 600;
            color: var(--secondary);
        }
        
        input, textarea {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        input:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.2);
        }
        
        textarea {
            min-height: 300px;
            resize: vertical;
        }
        
        .mood-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .mood-option {
            padding: 8px 15px;
            border-radius: 20px;
            background-color: rgba(106, 90, 205, 0.1);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .mood-option:hover {
            background-color: rgba(106, 90, 205, 0.2);
        }
        
        .mood-option.selected {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .buttons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        button {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-light);
        }
        
        .btn-secondary {
            background-color: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-secondary:hover {
            background-color: rgba(106, 90, 205, 0.1);
        }
        
        .btn-danger {
            background-color: #ff6b6b;
            color: var(--white);
        }
        
        .btn-danger:hover {
            background-color: #ff8787;
        }
        
        .no-entries {
            text-align: center;
            padding: 30px;
            color: #888;
        }
        
        .search-bar {
            margin-bottom: 20px;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%23666" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>');
            background-repeat: no-repeat;
            background-position: 15px center;
            padding-left: 45px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.2);
        }
        
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background-color: var(--primary);
            color: var(--white);
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transform: translateY(100px);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        
        .toast.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .journal-container {
                flex-direction: column;
            }
            
            .entries-section, .editor-section {
                min-width: 100%;
            }
            
            .buttons {
                flex-direction: column;
            }
            
            button {
                width: 100%;
            }
        }

        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100px;
        }

        .loading-spinner {
            border: 4px solid rgba(106, 90, 205, 0.1);
            border-top: 4px solid var(--primary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="background-animation" id="background-animation"></div>
    
    <header>
        <h1>My Personal Journal</h1>
        <div class="date-display" id="current-date"></div>
    </header>
    
    <div class="container">
        <div class="journal-container">
            <div class="entries-section">
                <div class="card">
                    <h2 class="section-title">My Entries</h2>
                    
                    <div class="search-bar">
                        <input type="text" class="search-input" id="search-input" placeholder="Search entries...">
                    </div>
                    
                    <div class="entry-list" id="entry-list">
                        <div class="loading">
                            <div class="loading-spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="editor-section">
                <div class="card">
                    <h2 class="section-title">Write Today's Entry</h2>
                    
                    <form class="editor-form" id="journal-form">
                        <div class="form-group">
                            <label for="entry-title">Title</label>
                            <input type="text" id="entry-title" placeholder="Give your entry a title...">
                        </div>
                        
                        <div class="form-group">
                            <label>How are you feeling today?</label>
                            <div class="mood-selector" id="mood-selector">
                                <div class="mood-option" data-mood="😊 Happy">😊 Happy</div>
                                <div class="mood-option" data-mood="😌 Calm">😌 Calm</div>
                                <div class="mood-option" data-mood="😐 Neutral">😐 Neutral</div>
                                <div class="mood-option" data-mood="😔 Sad">😔 Sad</div>
                                <div class="mood-option" data-mood="😠 Angry">😠 Angry</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="entry-content">Journal Entry</label>
                            <textarea id="entry-content" placeholder="Write your thoughts here..."></textarea>
                        </div>
                        
                        <div class="buttons">
                            <button type="submit" class="btn-primary" id="save-btn">Save Entry</button>
                            <button type="button" class="btn-secondary" id="new-entry-btn">New Entry</button>
                            <button type="button" class="btn-danger" id="delete-btn">Delete Entry</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>

    <script>
        // Initialize the journal application
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const currentDateDisplay = document.getElementById('current-date');
            const entryList = document.getElementById('entry-list');
            const journalForm = document.getElementById('journal-form');
            const entryTitleInput = document.getElementById('entry-title');
            const entryContentInput = document.getElementById('entry-content');
            const moodSelector = document.getElementById('mood-selector');
            const saveBtn = document.getElementById('save-btn');
            const newEntryBtn = document.getElementById('new-entry-btn');
            const deleteBtn = document.getElementById('delete-btn');
            const searchInput = document.getElementById('search-input');
            const toast = document.getElementById('toast');
            const backgroundAnimation = document.getElementById('background-animation');
            
            // State
            let entries = [];
            let currentEntryId = null;
            let selectedMood = null;
            
            // Initialize background animation
            createBackgroundAnimation();
            
            // Display current date
            updateCurrentDate();
            
            // Load entries from database
            loadEntriesFromDB();
            
            // Event listeners
            journalForm.addEventListener('submit', saveEntry);
            newEntryBtn.addEventListener('click', createNewEntry);
            deleteBtn.addEventListener('click', deleteEntry);
            searchInput.addEventListener('input', searchEntries);
            
            // Set up mood selector
            setupMoodSelector();
            
            // Functions
            function createBackgroundAnimation() {
                const bubbleCount = 15;
                
                for (let i = 0; i < bubbleCount; i++) {
                    const bubble = document.createElement('div');
                    bubble.classList.add('bubble');
                    
                    // Random properties
                    const size = Math.random() * 150 + 50;
                    const left = Math.random() * 100;
                    const delay = Math.random() * 10;
                    const duration = Math.random() * 20 + 10;
                    
                    bubble.style.width = ${size}px;
                    bubble.style.height = ${size}px;
                    bubble.style.left = ${left}%;
                    bubble.style.bottom = -${size}px;
                    bubble.style.animationDelay = ${delay}s;
                    bubble.style.animationDuration = ${duration}s;
                    
                    backgroundAnimation.appendChild(bubble);
                }
            }
            
            function updateCurrentDate() {
                const now = new Date();
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                currentDateDisplay.textContent = now.toLocaleDateString('en-US', options);
            }
            
            async function loadEntriesFromDB() {
                try {
                    const response = await fetch('?endpoint=entries');
                    
                    if (!response.ok) {
                        throw new Error('Failed to load entries');
                    }
                    
                    entries = await response.json();
                    displayEntries(entries);
                } catch (error) {
                    console.error('Error loading entries:', error);
                    showToast('Error loading entries. Please try again.');
                    displayNoEntries();
                }
            }
            
            function displayEntries(entriesToDisplay) {
                entryList.innerHTML = '';
                
                if (entriesToDisplay.length === 0) {
                    displayNoEntries();
                    return;
                }
                
                entriesToDisplay.forEach(entry => {
                    const entryItem = document.createElement('div');
                    entryItem.classList.add('entry-item');
                    if (entry.id == currentEntryId) {
                        entryItem.classList.add('active');
                    }
                    
                    entryItem.innerHTML = `
                        <div class="entry-title">${entry.mood ? entry.mood + ' ' : ''}${entry.title}</div>
                        <div class="entry-date">${formatDate(entry.created_at)}</div>
                        <div class="entry-preview">${entry.content.substring(0, 100)}${entry.content.length > 100 ? '...' : ''}</div>
                    `;
                    
                    entryItem.addEventListener('click', () => {
                        loadEntry(entry.id);
                    });
                    
                    entryList.appendChild(entryItem);
                });
            }
            
            function displayNoEntries() {
                entryList.innerHTML = '<div class="no-entries">No entries yet. Start writing!</div>';
            }
            
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
            }
            
            async function loadEntry(id) {
                try {
                    const response = await fetch(?endpoint=entry&id=${id});
                    
                    if (!response.ok) {
                        throw new Error('Failed to load entry');
                    }
                    
                    const entry = await response.json();
                    
                    currentEntryId = entry.id;
                    entryTitleInput.value = entry.title;
                    entryContentInput.value = entry.content;
                    
                    // Set the mood
                    selectMood(entry.mood);
                    
                    // Update UI to show active entry
                    const entryItems = document.querySelectorAll('.entry-item');
                    entryItems.forEach(item => item.classList.remove('active'));
                    const activeItem = document.querySelector(.entry-item:nth-child(${entries.findIndex(e => e.id == id) + 1}));
                    if (activeItem) {
                        activeItem.classList.add('active');
                    }
                } catch (error) {
                    console.error('Error loading entry:', error);
                    showToast('Error loading entry. Please try again.');
                }
            }
            
            async function saveEntry(e) {
                e.preventDefault();
                
                const title = entryTitleInput.value.trim();
                const content = entryContentInput.value.trim();
                
                if (!title || !content) {
                    showToast('Please fill in both title and content');
                    return;
                }
                
                const entryData = {
                    title,
                    content,
                    mood: selectedMood
                };
                
                try {
                    let response;
                    
                    if (currentEntryId) {
                        // Update existing entry
                        response = await fetch(?endpoint=entry&id=${currentEntryId}, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(entryData)
                        });
                    } else {
                        // Create new entry
                        response = await fetch('?endpoint=entry', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(entryData)
                        });
                    }
                    
                    if (!response.ok) {
                        throw new Error('Failed to save entry');
                    }
                    
                    const result = await response.json();
                    
                    if (!currentEntryId) {
                        currentEntryId = result.id;
                    }
                    
                    // Reload entries to update the list
                    await loadEntriesFromDB();
                    
                    showToast('Entry saved successfully!');
                } catch (error) {
                    console.error('Error saving entry:', error);
                    showToast('Error saving entry. Please try again.');
                }
            }
            
            function createNewEntry() {
                currentEntryId = null;
                entryTitleInput.value = '';
                entryContentInput.value = '';
                selectMood(null);
                
                // Update UI
                const entryItems = document.querySelectorAll('.entry-item');
                entryItems.forEach(item => item.classList.remove('active'));
                
                entryTitleInput.focus();
            }
            
            async function deleteEntry() {
                if (!currentEntryId) {
                    showToast('No entry selected to delete');
                    return;
                }
                
                if (confirm('Are you sure you want to delete this entry?')) {
                    try {
                        const response = await fetch(?endpoint=entry&id=${currentEntryId}, {
                            method: 'DELETE'
                        });
                        
                        if (!response.ok) {
                            throw new Error('Failed to delete entry');
                        }
                        
                        // Reload entries to update the list
                        await loadEntriesFromDB();
                        
                        createNewEntry();
                        showToast('Entry deleted successfully!');
                    } catch (error) {
                        console.error('Error deleting entry:', error);
                        showToast('Error deleting entry. Please try again.');
                    }
                }
            }
            
            function searchEntries() {
                const searchTerm = searchInput.value.trim().toLowerCase();
                
                if (!searchTerm) {
                    displayEntries(entries);
                    return;
                }
                
                const filteredEntries = entries.filter(entry => 
                    entry.title.toLowerCase().includes(searchTerm) || 
                    entry.content.toLowerCase().includes(searchTerm) ||
                    (entry.mood && entry.mood.toLowerCase().includes(searchTerm))
                );
                
                displayEntries(filteredEntries);
            }
            
            function setupMoodSelector() {
                const moodOptions = document.querySelectorAll('.mood-option');
                
                moodOptions.forEach(option => {
                    option.addEventListener('click', () => {
                        const mood = option.getAttribute('data-mood');
                        selectMood(mood);
                    });
                });
            }
            
            function selectMood(mood) {
                selectedMood = mood;
                
                const moodOptions = document.querySelectorAll('.mood-option');
                moodOptions.forEach(option => {
                    option.classList.remove('selected');
                    if (mood && option.getAttribute('data-mood') === mood) {
                        option.classList.add('selected');
                    }
                });
            }
            
            function showToast(message) {
                toast.textContent = message;
                toast.classList.add('show');
                
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
            }
        });
    </script>
</body>
</html>
<?php
}
?>